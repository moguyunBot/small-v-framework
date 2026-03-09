<?php
namespace app\admin\middleware;

use ReflectionException;
use support\exception\BusinessException;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use app\admin\model\Role;
use app\admin\model\Rule;

class Auth implements MiddlewareInterface
{
    /**
     * @param Request $request
     * @param callable $handler
     * @return Response
     * @throws ReflectionException|BusinessException
     */
    public function process(Request $request, callable $handler): Response
    {
        $controller = $request->controller;
        $action = $request->action;
        $code = 0;
        $msg = '';
        if (!self::canAccess($controller, $action, $code, $msg)) {
            if ($request->expectsJson()) {
                $response = json(['code' => $code, 'msg' => $msg, 'data' => []]);
            } else {
                if ($code === 401) {
                  $response = admin_error_401_script();
                } else {
                    // 返回403无权限页面
                    $response = jump($msg, '', 0, 'error', '403 - 无权限访问');
                }
            }

        } else {
            $response = $request->method() == 'OPTIONS' ? response('') : $handler($request);
        }

        return $response;

    }
    public static function canAccess(string $controller, string $action, int &$code = 0, string &$msg = ''): bool
    {
        // 无控制器信息说明是函数调用，函数不属于任何控制器，鉴权操作应该在函数内部完成。
        if (!$controller) {
            return true;
        }
        // 获取控制器鉴权信息
        $class = new \ReflectionClass($controller);
        $properties = $class->getDefaultProperties();
        $noNeedLogin = $properties['noNeedLogin'] ?? [];
        $noNeedAuth = $properties['noNeedAuth'] ?? [];

        // 不需要登录
        if (in_array($action, $noNeedLogin)) {
            return true;
        }

        // 获取登录信息
        $admin = admin();
        if (!$admin) {
            $msg = '请登录';
            // 401是未登录固定的返回码
            $code = 401;
            return false;
        }

        // 不需要鉴权
        if (in_array($action, $noNeedAuth)) {
            return true;
        }

        // 当前管理员无角色
        $roles = $admin['roles'];
        if (!$roles) {
            $msg = '无权限';
            $code = 2;
            return false;
        }

        // 角色没有规则
        $rules = Role::whereIn('id', $roles)->column('rules');
        $rule_ids = [];
        foreach ($rules as $rule_string) {
            if (!$rule_string) {
                continue;
            }
            $rule_ids = array_merge($rule_ids, explode(',', $rule_string));
        }
        if (!$rule_ids) {
            $msg = '无权限';
            $code = 2;
            return false;
        }

        // 超级管理员
        if (in_array('*', $rule_ids)){
            return true;
        }

        // 如果action为index，规则里有任意一个以$controller开头的权限即可
        if (strtolower($action) === 'index') {
            $controller_name = class_basename($controller);
            $rule = Rule::where(function ($query) use ($controller_name, $action) {
                $query->where('href', 'like', "/admin/$controller_name/%")->whereOr('href', "/admin/$controller_name");
            })->whereIn('id', $rule_ids)->find();
            if ($rule) {
                return true;
            }
            $msg = '无权限';
            $code = 2;
            return false;
        }

        // 查询是否有当前控制器的规则
        $controller_name = class_basename($controller);
        $rule = Rule::where(function ($query) use ($controller_name, $action) {
            $query->where('href', "/admin/$controller_name/$action")->whereOr('href', "/admin/$controller_name");
        })->whereIn('id', $rule_ids)->find();

        if (!$rule) {
            $msg = '无权限';
            $code = 2;
            return false;
        }

        return true;
    }
}
