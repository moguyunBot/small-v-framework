<?php
namespace app\admin\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use app\admin\model\Role;
use app\admin\model\Rule;

/**
 * 后台权限验证中间件
 */
class Auth implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $controller = $request->controller;
        $action     = $request->action;

        // 无控制器（函数路由），放行
        if (!$controller) {
            return $handler($request);
        }

        // 读取控制器白名单配置
        $props      = self::getControllerProps($controller);
        $noLogin    = $props['noNeedLogin'] ?? [];
        $noAuth     = $props['noNeedAuth']  ?? [];

        // 白名单：无需登录
        if (in_array($action, $noLogin, true)) {
            return $handler($request);
        }

        // 检查登录
        $admin = admin();
        if (!$admin) {
            return self::deny($request, 401, '请登录');
        }

        // 白名单：已登录无需鉴权
        if (in_array($action, $noAuth, true)) {
            return $handler($request);
        }

        // 获取角色规则 ID
        $ruleIds = self::getRuleIds($admin['roles'] ?? []);

        // 构建当前路径
        $plugin = $request->plugin ?? '';
        $ctrl   = class_basename($controller);
        $path   = $plugin
            ? '/app/' . $plugin . '/admin/' . $ctrl . '/' . $action
            : '/admin/' . $ctrl . '/' . $action;

        // 验证菜单节点是否存在（精确匹配当前路径，超级管理员也需要通过此检查）
        $nodeExists = Rule::where('href', $path)
            ->where('status', 1)
            ->count();
        if (!$nodeExists) {
            return self::deny($request, 404, '菜单节点不存在：' . $path);
        }

        // 超级管理员，节点存在即放行
        if (in_array('*', $ruleIds, true)) {
            return $handler($request);
        }

        // 验证当前管理员是否拥有该节点权限
        if (empty($ruleIds)) {
            return self::deny($request, 403, '无权限');
        }

        $hasPermission = Rule::whereIn('id', $ruleIds)
            ->where('status', 1)
            ->where(function ($q) use ($path, $plugin, $ctrl) {
                // 精确匹配当前路径
                $q->where('href', $path);
                // 或匹配同控制器下任意节点（拥有该控制器任意权限即可访问）
                $prefix = $plugin
                    ? '/app/' . $plugin . '/admin/' . $ctrl . '/'
                    : '/admin/' . $ctrl . '/';
                $q->whereOr('href', 'like', $prefix . '%');
            })
            ->count();

        if (!$hasPermission) {
            return self::deny($request, 403, '无权限访问');
        }

        return $handler($request);
    }

    /**
     * 读取控制器默认属性（noNeedLogin / noNeedAuth）
     */
    protected static function getControllerProps(string $controller): array
    {
        try {
            return (new \ReflectionClass($controller))->getDefaultProperties();
        } catch (\ReflectionException) {
            return [];
        }
    }

    /**
     * 从角色列表获取所有规则 ID
     */
    protected static function getRuleIds(array $roles): array
    {
        if (empty($roles)) {
            return [];
        }
        $ruleIds = [];
        foreach (Role::whereIn('id', $roles)->column('rules') as $str) {
            if (!empty($str)) {
                array_push($ruleIds, ...explode(',', $str));
            }
        }
        return array_unique($ruleIds);
    }

    /**
     * 统一拒绝响应
     */
    protected static function deny(Request $request, int $code, string $msg): Response
    {
        if ($request->expectsJson()) {
            return json(['code' => $code, 'msg' => $msg, 'data' => []]);
        }
        return $code === 401
            ? admin_error_401_script()
            : jump($msg, '', 0, 'error', $code . ' - ' . $msg);
    }
}
