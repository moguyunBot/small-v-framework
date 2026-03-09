<?php
namespace support;

use Webman\Http\Request as WebmanRequest;

/**
 * 自定义 Request 类
 * 实现全局输入过滤和安全检测
 */
class Request extends WebmanRequest
{
    /**
     * 过滤规则
     * @var array
     */
    protected $filter = ['trim', 'strip_tags', 'htmlspecialchars'];
    
    /**
     * 富文本字段白名单（不进行 strip_tags 和 htmlspecialchars）
     * @var array
     */
    protected $richTextFields = ['content', 'description', 'detail', 'body', 'editor'];
    
    /**
     * 是否已检测过（避免重复检测）
     * @var bool
     */
    private static $detected = false;
    
    /**
     * 获取 POST 参数
     * @param string|null $name
     * @param mixed $default
     * @return mixed
     */
    public function post(?string $name = null, mixed $default = null): mixed
    {
        // 首次调用时进行安全检测（在过滤之前）
        if (!self::$detected && $this->method() === 'POST') {
            $this->detectAttacks();
            self::$detected = true;
        }
        
        $data = parent::post($name, $default);
        
        if ($name === null) {
            // 获取所有 POST 数据时进行过滤
            return $this->filterData($data);
        }
        
        // 获取单个字段时进行过滤
        return $this->filterValue($name, $data);
    }
    
    /**
     * 获取 GET 参数
     * @param string|null $name
     * @param mixed $default
     * @return mixed
     */
    public function get(?string $name = null, mixed $default = null): mixed
    {
        // 首次调用时进行安全检测
        if (!self::$detected && $this->method() === 'GET') {
            $this->detectAttacks();
            self::$detected = true;
        }
        
        $data = parent::get($name, $default);
        
        if ($name === null) {
            return $this->filterData($data);
        }
        
        return $this->filterValue($name, $data);
    }
    
    /**
     * 获取请求参数（GET 或 POST）
     * @param string|null $name
     * @param mixed $default
     * @return mixed
     */
    public function input(?string $name = null, mixed $default = null): mixed
    {
        // 首次调用时进行安全检测
        if (!self::$detected) {
            $this->detectAttacks();
            self::$detected = true;
        }
        
        $data = parent::input($name, $default);
        
        if ($name === null) {
            return $this->filterData($data);
        }
        
        return $this->filterValue($name, $data);
    }
    
    /**
     * 安全检测（在过滤之前）
     */
    protected function detectAttacks()
    {
        $rawGet = parent::get() ?? [];
        $rawPost = parent::post() ?? [];
        
        // 1. XSS 攻击检测
        $this->detectXSS($rawGet, $rawPost);
        
        // 2. SQL 注入检测
        $this->detectSQLInjection($rawGet, $rawPost);
        
        // 3. 路径穿越检测
        $this->detectPathTraversal($rawGet, $rawPost);
        
        // 4. 文件上传攻击检测
        $this->detectFileUploadAttack();
    }
    
    /**
     * XSS 攻击检测
     */
    protected function detectXSS($get, $post)
    {
        $params = array_merge($get, $post);
        
        $xssPatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
        ];
        
        $this->checkParams($params, $xssPatterns, 'XSS');
    }
    
    /**
     * SQL 注入检测
     */
    protected function detectSQLInjection($get, $post)
    {
        $params = array_merge($get, $post);
        
        $sqlPatterns = [
            '/union.*select/i',
            '/select.*from/i',
            '/insert.*into/i',
            '/delete.*from/i',
            '/drop.*table/i',
            '/update.*set/i',
            '/exec.*\(/i',
            '/execute.*\(/i',
            '/;.*drop/i',
            '/;.*delete/i',
        ];
        
        $this->checkParams($params, $sqlPatterns, 'SQL Injection');
    }
    
    /**
     * 路径穿越检测
     */
    protected function detectPathTraversal($get, $post)
    {
        $params = array_merge($get, $post);
        
        foreach ($params as $key => $value) {
            $this->checkValue($key, $value, function($val) {
                return strpos($val, '../') !== false || strpos($val, '..\\') !== false;
            }, 'Path Traversal');
        }
    }
    
    /**
     * 文件上传攻击检测
     */
    protected function detectFileUploadAttack()
    {
        $files = $this->file();
        if (empty($files)) {
            return;
        }
        
        foreach ($files as $key => $file) {
            if (is_array($file)) {
                foreach ($file as $f) {
                    $this->checkFile($f, $key);
                }
            } else {
                $this->checkFile($file, $key);
            }
        }
    }
    
    /**
     * 检查单个文件
     */
    protected function checkFile($file, $key)
    {
        // 如果是数组，递归检查每个文件
        if (is_array($file)) {
            foreach ($file as $index => $singleFile) {
                $this->checkFile($singleFile, $key . '[' . $index . ']');
            }
            return;
        }
        
        if (!$file || !$file->isValid()) {
            return;
        }
        
        $ext = strtolower($file->getUploadExtension());
        $dangerousExt = ['php', 'php3', 'php4', 'php5', 'phtml', 'pht', 'jsp', 
                         'asp', 'aspx', 'sh', 'bash', 'cgi', 'pl', 'py', 'rb', 
                         'exe', 'dll', 'so', 'bat', 'cmd'];
        
        if (in_array($ext, $dangerousExt)) {
            $this->logAttack('Dangerous File Upload', $key, $ext);
        }
    }
    
    /**
     * 检查参数
     */
    protected function checkParams($params, $patterns, $type)
    {
        foreach ($params as $key => $value) {
            $this->checkValue($key, $value, function($val) use ($patterns) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $val)) {
                        return true;
                    }
                }
                return false;
            }, $type);
        }
    }
    
    /**
     * 检查单个值（支持递归）
     */
    protected function checkValue($key, $value, $checker, $type)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $this->checkValue($key . '[' . $k . ']', $v, $checker, $type);
            }
            return;
        }
        
        // 跳过富文本字段的检测
        if ($this->isRichTextField($key)) {
            return;
        }
        
        if (is_string($value) && $checker($value)) {
            $this->logAttack($type, $key, $value);
        }
    }
    
    /**
     * 记录攻击日志
     */
    protected function logAttack($type, $key, $value)
    {
        $logData = [
            'type' => $type,
            'time' => date('Y-m-d H:i:s'),
            'ip' => $this->getRealIp(),
            'url' => $this->url(),
            'method' => $this->method(),
            'param' => $key,
            'value' => substr($value, 0, 200), // 只记录前200个字符
            'user_agent' => $this->header('user-agent'),
        ];
        
        // 记录到日志文件
        $logFile = runtime_path() . '/logs/security_' . date('Y-m-d') . '.log';
        $logContent = json_encode($logData, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        @file_put_contents($logFile, $logContent, FILE_APPEND);
    }
    
    /**
     * 过滤数据
     * @param mixed $data
     * @param string $prefix 字段前缀（用于递归时构建完整字段名）
     * @return mixed
     */
    protected function filterData($data, $prefix = '')
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $fullKey = $prefix ? $prefix . '[' . $key . ']' : $key;
                $data[$key] = $this->filterData($value, $fullKey);
            }
            return $data;
        }
        
        if (!is_string($data)) {
            return $data;
        }
        
        // 检查是否是富文本字段
        if ($prefix && $this->isRichTextField($prefix)) {
            return $this->filterRichText($data);
        }
        
        // 应用过滤规则
        foreach ($this->filter as $filter) {
            if (function_exists($filter)) {
                $data = $filter($data);
            }
        }
        
        return $data;
    }
    
    /**
     * 过滤单个值
     * @param string $name 字段名
     * @param mixed $value 值
     * @return mixed
     */
    protected function filterValue($name, $value)
    {
        // 检查是否是富文本字段
        if ($this->isRichTextField($name)) {
            return $this->filterRichText($value);
        }
        
        return $this->filterData($value);
    }
    
    /**
     * 检查是否是富文本字段
     * @param string $name
     * @return bool
     */
    protected function isRichTextField($name)
    {
        // 支持嵌套字段检查，如 basic[config][editor][value]
        foreach ($this->richTextFields as $field) {
            if (strpos($name, '[' . $field . ']') !== false || 
                strpos($name, $field) === 0 ||
                preg_match('/\[' . preg_quote($field, '/') . '\]/', $name)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * 过滤富文本内容
     * @param mixed $data
     * @return mixed
     */
    protected function filterRichText($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->filterRichText($value);
            }
            return $data;
        }
        
        if (!is_string($data)) {
            return $data;
        }
        
        // 检查是否包含 HTML 标签
        if (!preg_match('/<[^>]+>/', $data)) {
            // 不包含 HTML，按普通文本处理
            return htmlspecialchars(trim($data), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        // 允许的 HTML 标签
        $allowedTags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><table><thead><tbody><tr><th><td><blockquote><pre><code><span><div>';
        
        // 只保留允许的标签
        $data = strip_tags($data, $allowedTags);
        
        // 过滤危险属性（on* 事件）
        $data = preg_replace('/<(\w+)[^>]*\s(on\w+)\s*=\s*["\']?[^"\']*["\']?/i', '<$1', $data);
        
        // 过滤 javascript: 协议
        $data = preg_replace('/href\s*=\s*["\']?\s*javascript:/i', 'href="#"', $data);
        $data = preg_replace('/src\s*=\s*["\']?\s*javascript:/i', 'src=""', $data);
        
        return $data;
    }
    
    /**
     * 设置过滤规则
     * @param array $filter
     * @return $this
     */
    public function setFilter(array $filter)
    {
        $this->filter = $filter;
        return $this;
    }
    
    /**
     * 添加富文本字段
     * @param string|array $fields
     * @return $this
     */
    public function addRichTextField($fields)
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }
        $this->richTextFields = array_merge($this->richTextFields, $fields);
        return $this;
    }
}
