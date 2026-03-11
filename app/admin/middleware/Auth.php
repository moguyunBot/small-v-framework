<?php
namespace app\admin\middleware;

use ReflectionClass;
use ReflectionException;
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
    /**
     * 控制器反射缓存
     * @var array<string, ReflectionClass>
     */
    protected static array $reflectionCache = [];

    /**
     * 处理请求
     * @throws ReflectionException|BusinessException
     */
    public function process(Request $request, callable $handler): Response
    {
        $controller = $request->controller;
        $action = $request->action;
        $code = 0;
        $msg = '';
        
        if (self::canAccess($controller, $action, $code, $msg)) {
            return $request->method() === 'OPTIONS' 
                ? response('') 
                : $handler($request);
        }

        // 无权访问，返回相应响应
        if ($request->expectsJson()) {
            return json(['code' => $code, 'msg' => $msg, 'data' => []]);
        }

        return $code === 401 
            ? admin_error_401_script()
            : jump($msg, '', 0, 'error', '403 - 无权限访问');
    }

    /**
     * 检查是否有权限访问
     */
    public static function canAccess(?string $controller, string $action, int &$code = 0, string &$msg = ''): bool
    {
        // 无控制器信息（函数调用），鉴权应在函数内部完成
        if (!$controller) {
            return true;
        }

        $properties = self::getControllerProperties($controller);
        $noNeedLogin = $properties['noNeedLogin'] ?? [];
        $noNeedAuth = $properties['noNeedAuth'] ?? [];

        // 不需要登录
        if (in_array($action, $noNeedLogin, true)) {
            return true;
        }

        // 检查登录
        $admin = admin();
        if (!$admin) {
            $msg = '请登录';
            $code = 401;
            return false;
        }

        // 不需要鉴权
        if (in_array($action, $noNeedAuth, true)) {
            return true;
        }

        // 获取管理员权限规则
        $ruleIds = self::getAdminRuleIds($admin['roles'] ?? []);
        
        if (empty($ruleIds)) {
            $msg = '无权限';
            $code = 2;
            return false;
        }

        // 超级管理员
        if (in_array('*', $ruleIds, true)) {
            return true;
        }

        // 检查具体权限
        return self::checkPermission($action, $controller, $ruleIds, $code, $msg);
    }

    /**
     * 获取控制器属性（带缓存）
     * @throws ReflectionException
     */
    protected static function getControllerProperties(string $controller): array
    {
        if (!isset(self::$reflectionCache[$controller])) {
            self::$reflectionCache[$controller] = new ReflectionClass($controller);
        }
        
        return self::$reflectionCache[$controller]->getDefaultProperties();
    }

    /**
     * 获取管理员的权限规则ID列表
     */
    protected static function getAdminRuleIds(array $roles): array
    {
        if (empty($roles)) {
            return [];
        }

        $rules = Role::whereIn('id', $roles)->column('rules');
        $ruleIds = [];
        
        foreach ($rules as $ruleString) {
            if (empty($ruleString)) {
                continue;
            }
            $ruleIds = array_merge($ruleIds, explode(',', $ruleString));
        }

        return array_unique($ruleIds);
    }

    /**
     * 检查具体权限
     */
    protected static function checkPermission(
        string $action, 
        string $controller, 
        array $ruleIds, 
        int &$code, 
        string &$msg
    ): bool {
        $controllerName = class_basename($controller);
        
        // 获取当前请求路径
        $path = request()->path();
        
        // 判断是系统权限还是插件权限（通过控制器命名空间判断）
        if (str_starts_with($controller, 'plugin\\')) {
            // 插件权限检查
            return self::checkPluginPermission($path, $ruleIds, $code, $msg);
        }
        
        // 系统权限检查
        return self::checkSystemPermission($action, $controllerName, $ruleIds, $code, $msg);
    }
    
    /**
     * 检查系统权限
     */
    protected static function checkSystemPermission(
        string $action,
        string $controllerName,
        array $ruleIds,
        int &$code,
        string &$msg
    ): bool {
        // index 方法特殊处理：有该控制器任意权限即可
        if (strtolower($action) === 'index') {
            $rule = Rule::whereIn('id', $ruleIds)
                ->where('type', 'system')
                ->where(function ($query) use ($controllerName) {
                    $query->where('href', 'like', "/admin/{$controllerName}/%")
                          ->whereOr('href', "/admin/{$controllerName}");
                })
                ->find();
        } else {
            // 其他方法：精确匹配
            $rule = Rule::whereIn('id', $ruleIds)
                ->where('type', 'system')
                ->where(function ($query) use ($controllerName, $action) {
                    $query->where('href', "/admin/{$controllerName}/{$action}")
                          ->whereOr('href', "/admin/{$controllerName}");
                })
                ->find();
        }

        if ($rule) {
            return true;
        }

        $msg = '无权限';
        $code = 2;
        return false;
    }
    
    /**
     * 检查插件权限
     * 路径格式：/app/{plugin}/admin/{controller}/{action}
     * 匹配规则：精确匹配当前路径，或匹配同控制器前缀（允许 add/edit/del 等子操作）
     */
    protected static function checkPluginPermission(
        string $path,
        array $ruleIds,
        int &$code,
        string &$msg
    ): bool {
        // 从路径提取插件标识
        $parts  = explode('/', trim($path, '/'));
        $plugin = $parts[1] ?? '';

        if (empty($plugin)) {
            $msg  = '无插件权限';
            $code = 403;
            return false;
        }

        // 提取控制器前缀（去掉最后一段 action）
        // 例：/app/blog/admin/post/add → /app/blog/admin/post/
        $actionParts = $parts;
        array_pop($actionParts);
        $prefix = '/' . implode('/', $actionParts) . '/';

        // 精确匹配当前路径，或前缀匹配同控制器下的所有操作
        $rule = Rule::whereIn('id', $ruleIds)
            ->where('type', 'plugin')
            ->where('plugin', $plugin)
            ->where('status', 1)
            ->where(function ($query) use ($path, $prefix) {
                $query->where('href', $path)
                      ->whereOr('href', 'like', $prefix . '%');
            })
            ->find();

        if ($rule) {
            return true;
        }

        $msg  = '无插件权限';
        $code = 403;
        return false;
    }
}
