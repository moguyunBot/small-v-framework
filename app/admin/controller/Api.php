<?php

namespace app\admin\controller;

use support\Request;
use support\Db;

/**
 * API 接口管理
 */
class Api extends Base
{
    /**
     * API 接口列表
     */
    public function index()
    {
        if ($this->isPost()) {
            $action = $this->post['action'] ?? '';
            
            try {
                switch ($action) {
                    case 'list':
                        $routes = $this->getRouteList();
                        return json(['code' => 0, 'data' => $routes]);
                        
                    case 'test':
                        $url = $this->post['url'] ?? '';
                        $method = $this->post['method'] ?? 'GET';
                        $params = $this->post['params'] ?? [];
                        $headers = $this->post['headers'] ?? [];
                        $result = $this->testApi($url, $method, $params, $headers);
                        return json(['code' => 0, 'data' => $result]);
                        
                    default:
                        return error('未知操作');
                }
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
        }
        
        return $this->view('api/index');
    }
    
    /**
     * 获取路由列表
     */
    protected function getRouteList()
    {
        $routes = [];
        $routeFile = base_path() . '/config/route.php';
        
        if (file_exists($routeFile)) {
            // 读取路由配置
            $routeConfig = include $routeFile;
        }
        
        // 扫描控制器目录
        $controllerPath = app_path() . '/admin/controller';
        $controllers = $this->scanControllers($controllerPath);
        
        foreach ($controllers as $controller) {
            $reflection = new \ReflectionClass($controller);
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
            
            foreach ($methods as $method) {
                // 跳过继承的方法和魔术方法
                if ($method->class !== $controller || strpos($method->name, '__') === 0) {
                    continue;
                }
                
                // 跳过 Base 类的方法
                if (in_array($method->name, ['view', 'isPost', 'json', 'redirect'])) {
                    continue;
                }
                
                // 解析注释
                $docComment = $method->getDocComment();
                $description = $this->parseDocComment($docComment);
                
                // 生成路由路径
                $controllerName = str_replace('app\\admin\\controller\\', '', $controller);
                $methodName = $method->name;
                $path = '/admin/' . $controllerName . '/' . $methodName;
                
                $routes[] = [
                    'path' => $path,
                    'method' => 'GET/POST',
                    'controller' => $controllerName,
                    'action' => $methodName,
                    'description' => $description['title'] ?: $methodName,
                    'params' => $description['params'],
                    'return' => $description['return']
                ];
            }
        }
        
        return $routes;
    }
    
    /**
     * 扫描控制器
     */
    protected function scanControllers($path, $namespace = 'app\\admin\\controller')
    {
        $controllers = [];
        
        if (!is_dir($path)) {
            return $controllers;
        }
        
        $files = scandir($path);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $fullPath = $path . '/' . $file;
            
            if (is_dir($fullPath)) {
                // 递归扫描子目录
                $subNamespace = $namespace . '\\' . $file;
                $controllers = array_merge($controllers, $this->scanControllers($fullPath, $subNamespace));
            } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $className = $namespace . '\\' . pathinfo($file, PATHINFO_FILENAME);
                if (class_exists($className)) {
                    $controllers[] = $className;
                }
            }
        }
        
        return $controllers;
    }
    
    /**
     * 解析注释
     */
    protected function parseDocComment($docComment)
    {
        $result = [
            'title' => '',
            'params' => [],
            'return' => ''
        ];
        
        if (!$docComment) {
            return $result;
        }
        
        $lines = explode("\n", $docComment);
        
        foreach ($lines as $line) {
            $line = trim($line, " \t\n\r\0\x0B*/");
            
            if (empty($line)) {
                continue;
            }
            
            // 标题（第一行非标签的内容）
            if (empty($result['title']) && strpos($line, '@') !== 0) {
                $result['title'] = $line;
                continue;
            }
            
            // @param 参数
            if (strpos($line, '@param') === 0) {
                preg_match('/@param\s+(\S+)\s+\$(\S+)\s*(.*)/', $line, $matches);
                if (isset($matches[2])) {
                    $result['params'][] = [
                        'type' => $matches[1] ?? '',
                        'name' => $matches[2],
                        'description' => $matches[3] ?? ''
                    ];
                }
            }
            
            // @return 返回值
            if (strpos($line, '@return') === 0) {
                $result['return'] = trim(str_replace('@return', '', $line));
            }
        }
        
        return $result;
    }
    
    /**
     * 测试 API 接口
     */
    protected function testApi($url, $method, $params, $headers)
    {
        // 如果是相对路径，转换为完整 URL
        if (strpos($url, 'http') !== 0) {
            $request = request();
            $scheme = $request->header('x-forwarded-proto', $request->uri()['scheme'] ?? 'http');
            
            // 获取实际访问的域名（优先从请求头获取）
            $host = $request->header('x-forwarded-host') 
                    ?: $request->header('host') 
                    ?: $request->host();
            
            // 获取 webman 运行端口
            $processConfig = config('process');
            $port = 80; // 默认端口
            
            if (isset($processConfig['webman']['listen'])) {
                $listen = $processConfig['webman']['listen'];
                // 解析 listen 配置，格式如：http://0.0.0.0:2222
                if (preg_match('/:(\d+)$/', $listen, $matches)) {
                    $port = $matches[1];
                }
            }
            
            // 如果是反向代理，使用标准端口，否则使用实际端口
            $isProxy = $request->header('x-forwarded-proto') || $request->header('x-forwarded-for');
            
            // 如果 host 中已经包含端口号，不再添加
            if (!$isProxy && $port != 80 && $port != 443 && strpos($host, ':') === false) {
                $host .= ':' . $port;
            }
            
            $url = $scheme . '://' . $host . $url;
        }
        
        // 构建请求头
        $requestHeaders = [];
        if (!empty($headers)) {
            foreach ($headers as $key => $value) {
                $requestHeaders[] = $key . ': ' . $value;
            }
        }
        
        // 发送请求
        $startTime = microtime(true);
        $result = curl_request($url, $params, $method, $requestHeaders, false, 30);
        $endTime = microtime(true);
        
        return [
            'status' => $result['code'],
            'time' => round(($endTime - $startTime) * 1000, 2) . 'ms',
            'data' => $result['data'],
            'error' => $result['error']
        ];
    }
}
