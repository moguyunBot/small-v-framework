<?php
namespace app\admin\controller;

use app\admin\model\{Role, Rule};
use support\View;
use Webman\Http\Response;

/**
 * 后台基础控制器
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

        $this->loadModel();
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
     * 插件后台：plugin = 插件标识，平铺菜单
     * 主后台：plugin = ''，递归树形菜单
     */
    protected function generateMenu(): void
    {
        $request     = request();
        $controller  = class_basename($request->controller ?? '');
        $action      = $request->action ?? '';
        $plugin      = $request->plugin ?? '';
        $currentPath = $plugin
            ? '/app/' . $plugin . '/admin/' . $controller . '/' . $action
            : '/admin/' . $controller . '/' . $action;

        $rules = Rule::where('plugin', $plugin)
            ->where('status', 1)
            ->order('sort asc, id asc')
            ->select()->toArray();

        $html = Rule::recursion($rules, 0, $currentPath);
        View::assign('menu_html', $html);
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
    protected function view(array $vars = []): Response
    {
        $parts    = explode('\\', $this->controller);
        $ctrl     = strtolower(end($parts));
        $template = $ctrl . '/' . $this->action;
        View::assign('base_template', base_path() . '/app/admin/view/base.html');
        return view($template, $vars);
    }

}
