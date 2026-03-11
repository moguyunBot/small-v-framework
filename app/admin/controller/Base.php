<?php
namespace app\admin\controller;

use app\admin\model\{Role, Rule};
use support\View;
use Webman\Http\Response;

/**
 * 后台基础控制器
 * 所有主后台控制器都应该继承此类
 */
class Base
{
    protected $model = null;
    protected $noNeedLogin = [];
    protected $noNeedAuth  = [];

    public $post;
    public $get;
    public $request;
    public $controller;
    public $action;

    public function __construct()
    {
        $this->request    = $request = request();
        $this->post       = $request->post();
        $this->get        = $request->get();
        $this->controller = $request->controller;
        $this->action     = $request->action;

        // 注入 iframe 模式变量
        View::assign('iframe', !empty($this->get['iframe']) ? 1 : 0);

        // 注入超级管理员标识
        $admin        = admin();
        $isSuperAdmin = false;
        if ($admin && !empty($admin['roles'])) {
            $roles = Role::whereIn('id', $admin['roles'])->column('rules');
            foreach ($roles as $rules) {
                if ($rules === '*' || in_array('*', explode(',', $rules))) {
                    $isSuperAdmin = true;
                    break;
                }
            }
        }
        View::assign('isSuperAdmin', $isSuperAdmin);

        // 自动加载对应的模型
        $this->loadModel();

        // 生成后台菜单
        $this->generateMenu();
    }

    /**
     * 自动加载模型
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
     * 若是插件控制器，只显示当前插件的菜单
     */
    protected function generateMenu(): void
    {
        // 插件控制器：从插件 menu.php 生成菜单
        if (str_starts_with($this->controller ?? '', 'plugin\\')) {
            preg_match('/^plugin\\\\([^\\\\]+)/', $this->controller, $m);
            $identifier = $m[1] ?? '';
            $html = '';
            if ($identifier) {
                $menuFile = base_path("plugin/{$identifier}/config/menu.php");
                if (file_exists($menuFile)) {
                    $menu = require $menuFile;
                    foreach ($menu['menus'] ?? [] as $item) {
                        $html .= $this->renderPluginMenuItem($item);
                    }
                }
            }
            View::assign('menu_html', $html);
            return;
        }

        // 主后台：生成系统菜单
        $admin     = admin();
        $rule_ids  = $this->getAdminRuleIds($admin);
        $all_rules = $this->getAllRules($rule_ids);
        $trees     = Rule::recursion($all_rules);
        View::assign('menu_html', Rule::recursion_menu($trees));
    }

    /**
     * 渲染插件菜单项 HTML
     */
    protected function renderPluginMenuItem(array $item): string
    {
        $title    = htmlspecialchars($item['title'] ?? '');
        $href     = $item['href'] ?? '';
        $icon     = $item['icon'] ?? 'mdi mdi-circle-small';
        $children = $item['children'] ?? [];
        if ($children) {
            $sub = '';
            foreach ($children as $child) {
                $sub .= $this->renderPluginMenuItem($child);
            }
            return "<li class=\"nav-item has-sub\"><a class=\"nav-link\" href=\"javascript:void(0)\"><i class=\"{$icon}\"></i><span>{$title}</span></a><ul class=\"nav-sub\">{$sub}</ul></li>";
        }
        $uri      = strtolower(request()->path());
        $hrefLower = strtolower($href);
        // 精确匹配或前缀匹配（子路径也 active）
        $active = ($href && ($uri === $hrefLower || str_starts_with($uri, rtrim($hrefLower, '/index') . '/'))) ? ' active' : '';
        return "<li class=\"nav-item{$active}\"><a class=\"nav-link\" href=\"{$href}\"><i class=\"{$icon}\"></i><span>{$title}</span></a></li>";
    }

    /**
     * 获取管理员的权限规则ID列表
     */
    protected function getAdminRuleIds(?array $admin): array
    {
        $rule_ids = [];
        if ($admin && !empty($admin['roles'])) {
            $roles = Role::whereIn('id', $admin['roles'])->column('rules');
            foreach ($roles as $rule_string) {
                if (!$rule_string) continue;
                if ($rule_string === '*' || in_array('*', explode(',', $rule_string))) {
                    return ['*'];
                }
                $rule_ids = array_merge($rule_ids, explode(',', $rule_string));
            }
        }
        return $rule_ids;
    }

    /**
     * 获取规则列表（只取系统菜单）
     */
    protected function getAllRules(array $rule_ids): array
    {
        $all_rules = Rule::where('plugin', '')->order('sort asc, id desc')->select()->toArray();
        if (!in_array('*', $rule_ids)) {
            $all_rules = array_filter($all_rules, fn($rule) => in_array($rule['id'], $rule_ids));
        }
        return $all_rules;
    }

    /**
     * 仅超级管理员可执行的操作保护
     */
    protected function checkSuperAdmin(): ?Response
    {
        $admin = admin();
        if (!$admin || empty($admin['roles'])) {
            return error('无权限，仅超级管理员可执行此操作');
        }
        $roles = Role::whereIn('id', $admin['roles'])->column('rules');
        foreach ($roles as $rules) {
            if ($rules === '*' || in_array('*', explode(',', $rules))) {
                return null;
            }
        }
        return error('无权限，仅超级管理员可执行此操作');
    }

    public function isPost(): bool
    {
        return $this->request->method() === 'POST';
    }

    /**
     * 渲染视图
     */
    protected function view(?string $template = null, array $vars = []): Response
    {
        if (!$template) {
            $parts    = explode('\\', $this->controller);
            $ctrl     = strtolower(end($parts));
            $template = $ctrl . '/' . $this->action;
        }
        View::assign('base_template', base_path() . '/app/admin/view/base.html');
        return view($template, $vars);
    }

    /**
     * 获取角色权限规则列表
     */
    protected function getRules(array $roles): array
    {
        $rules_strings = $roles ? Role::whereIn('id', $roles)->column('rules') : [];
        $rules = [];
        foreach ($rules_strings as $rule_string) {
            if (!$rule_string) continue;
            $rules = array_merge($rules, explode(',', $rule_string));
        }
        return $rules;
    }
}
