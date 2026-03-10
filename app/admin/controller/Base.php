<?php
namespace app\admin\controller;

use app\admin\model\{Role, Rule};
use support\View;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 后台基础控制器
 * 所有后台控制器都应该继承此类
 * 
 * @package support\controller
 */
class Base
{
    /**
     * 数据模型实例
     * @var \think\Model|null
     */
    protected $model = null;

    /**
     * 无需登录及鉴权的方法列表
     * @var array<string>
     */
    protected $noNeedLogin = [];

    /**
     * 需要登录但无需鉴权的方法列表
     * @var array<string>
     */
    protected $noNeedAuth = [];
    
    /**
     * POST 请求数据
     * @var array
     */
    public $post;
    
    /**
     * GET 请求数据
     * @var array
     */
    public $get;
    
    /**
     * 请求对象
     * @var Request
     */
    public $request;
    
    /**
     * 控制器类名
     * @var string
     */
    public $controller;
    
    /**
     * 方法名
     * @var string
     */
    public $action;
    
    /**
     * 构造函数
     * 初始化请求数据、加载模型、生成菜单
     */
    public function __construct()
    {
        $this->request = $request = request();
        $this->post = $request->post();
        $this->get = $request->get();
        $this->controller = $request->controller;
        $this->action = $request->action;
        
        // 注入 iframe 模式变量
        View::assign('iframe', !empty($this->get['iframe']) ? 1 : 0);
        
        // 注入超级管理员标识
        $admin = admin();
        $isSuperAdmin = false;
        if ($admin && !empty($admin['roles'])) {
            $roles = \app\admin\model\Role::whereIn('id', $admin['roles'])->column('rules');
            foreach ($roles as $rules) {
                if ($rules === '*' || in_array('*', explode(',', $rules))) {
                    $isSuperAdmin = true;
                    break;
                }
            }
        }
        View::assign('isSuperAdmin', $isSuperAdmin);
        
        // 自动加载对应的模型（排除插件控制器，避免循环）
        $this->loadModel();
        
        // 生成后台菜单
        // 生成后台菜单（插件控制器跳过，在 view() 里按需生成插件菜单）
        if (!str_starts_with($this->controller ?? '', 'plugin\\')) {
            $this->generateMenu();
        }
    }
    
    /**
     * 自动加载模型
     * @return void
     */
    protected function loadModel(): void
    {
        $controllerClass = str_replace('controller', 'model', $this->controller);
        if (class_exists($controllerClass)) {
            $this->model = new $controllerClass();
        }
    }
    
    /**
     * 生成后台菜单
     * @return void
     */
    protected function generateMenu(): void
    {
        // 获取当前管理员的权限规则ID
        $admin = admin();
        $rule_ids = $this->getAdminRuleIds($admin);
        
        // 获取所有规则
        $all_rules = $this->getAllRules($rule_ids);
        
        // 生成菜单树和HTML
        $trees = Rule::recursion($all_rules);
        $menu_html = Rule::recursion_menu($trees);
        
        View::assign('menu_html', $menu_html);
    }
    
    /**
     * 获取管理员的权限规则ID列表
     * @param array|null $admin 管理员信息
     * @return array<string>
     */
    protected function getAdminRuleIds(?array $admin): array
    {
        $rule_ids = [];
        
        if ($admin && !empty($admin['roles'])) {
            $roles = Role::whereIn('id', $admin['roles'])->column('rules');
            foreach ($roles as $rule_string) {
                if (!$rule_string) {
                    continue;
                }
                // 如果是超级管理员（包含 * 权限），显示所有菜单
                if ($rule_string === '*' || in_array('*', explode(',', $rule_string))) {
                    return ['*'];
                }
                $rule_ids = array_merge($rule_ids, explode(',', $rule_string));
            }
        }
        
        return $rule_ids;
    }
    
    /**
     * 获取所有规则
     * @param array<string> $rule_ids 权限规则ID列表
     * @return array
     */
    protected function getAllRules(array $rule_ids): array
    {
        // 只取系统菜单（plugin=''），插件菜单不显示在后台导航
        $ruleModel = Rule::where('plugin', '')->order('sort asc, id desc');
        
        $all_rules = $ruleModel->select()->toArray();
        
        // 如果不是超级管理员，过滤出有权限的规则
        if (!in_array('*', $rule_ids)) {
            $all_rules = array_filter($all_rules, function($rule) use ($rule_ids) {
                return in_array($rule['id'], $rule_ids);
            });
        }
        
        return $all_rules;
    }
    
    /**
     * 如果不是超级管理员则返回错误（用于插件敏感操作保护）
     */
    protected function checkSuperAdmin(): ?\Webman\Http\Response
    {
        $admin = admin();
        if (!$admin || empty($admin['roles'])) {
            return error('无权限，仅超级管理员可执行此操作');
        }
        $roles = \app\admin\model\Role::whereIn('id', $admin['roles'])->column('rules');
        foreach ($roles as $rules) {
            if ($rules === '*' || in_array('*', explode(',', $rules))) {
                return null; // 是超管，放行
            }
        }
        return error('无权限，仅超级管理员可执行此操作');
    }

    /**
     * 判断是否为 POST 请求
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->request->method() === 'POST';
    }
    
    /**
     * 渲染视图（自动根据控制器和方法名称查找模板）
     * @param string|null $template 模板路径，为空则自动推断
     * @param array $vars 模板变量
     * @return Response
     */
    protected function view(?string $template = null, array $vars = []): Response
    {
        if ($template === null || $template === '') {
            $template = $this->getAutoTemplate();
        }
        
        // 注入 base 模板绝对路径，供插件视图使用 {extend name="$base_template"}
        View::assign('base_template', base_path() . '/app/admin/view/base.html');

        // 若是插件控制器，覆盖 menu_html 为插件自己的菜单
        if (str_starts_with($this->controller ?? '', 'plugin\\')) {
            preg_match('/^plugin\\\\([^\\\\]+)/', $this->controller, $m);
            $identifier = $m[1] ?? '';
            if ($identifier) {
                $menuFile = base_path("plugin/{$identifier}/config/menu.php");
                if (file_exists($menuFile)) {
                    $menu = (function($f) { return include $f; })($menuFile);
                    $html = '';
                    foreach ($menu['menus'] ?? [] as $item) {
                        $html .= $this->renderPluginMenu($item);
                    }
                    View::assign('menu_html', $html);
                }
            }
        }
        
        return view($template, $vars);
    }

    /**
     * 渲染插件菜单项为 HTML
     */
    protected function renderPluginMenu(array $item, int $level = 0): string
    {
        $title    = htmlspecialchars($item['title'] ?? '');
        $href     = $item['href'] ?? '';
        $icon     = $item['icon'] ?? 'mdi mdi-circle-small';
        $children = $item['children'] ?? [];

        if ($children) {
            $sub = '';
            foreach ($children as $child) {
                $sub .= $this->renderPluginMenu($child, $level + 1);
            }
            return "<li class=\"nav-item has-sub\"><a class=\"nav-link\" href=\"javascript:void(0)\"><i class=\"{$icon}\"></i><span>{$title}</span></a><ul class=\"nav-sub\">{$sub}</ul></li>";
        }

        $active = ($href && str_contains($_SERVER['REQUEST_URI'] ?? '', $href)) ? ' active' : '';
        return "<li class=\"nav-item{$active}\"><a class=\"nav-link\" href=\"{$href}\"><i class=\"{$icon}\"></i><span>{$title}</span></a></li>";
    }
    
    /**
     * 自动推断模板路径
     * @return string
     */
    protected function getAutoTemplate(): string
    {
        $controller = $this->controller;
        $action = $this->action;
        
        // 普通控制器路径
        // app\admin\controller\User -> user/index
        $parts = explode('\\', $controller);
        $ctrl = strtolower(end($parts));
        return $ctrl . '/' . $action;
    }
    
    /**
     * 获取角色权限规则列表
     * @param array $roles 角色ID列表
     * @return array<string>
     */
    protected function getRules(array $roles): array
    {
        $rules_strings = $roles ? Role::whereIn('id', $roles)->column('rules') : [];
        $rules = [];
        
        foreach ($rules_strings as $rule_string) {
            if (!$rule_string) {
                continue;
            }
            $rules = array_merge($rules, explode(',', $rule_string));
        }
        
        return $rules;
    }
}
