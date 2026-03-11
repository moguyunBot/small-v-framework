<?php
namespace app\admin\controller;

use app\admin\model\Config as ConfigModel;
use app\admin\model\ConfigGroup as ConfigGroupModel;

/**
 * 开发者工具控制器
 */
class Dev extends Base
{
    /**
     * 表单构建器
     * @return \Webman\Http\Response
     */
    public function formBuild()
    {
        return view();
    }
    
    /**
     * 缓存管理
     * @return \Webman\Http\Response
     */
    public function cache()
    {
        if ($this->isPost()) {
            $action = $this->post['action'] ?? '';
            
            try {
                switch ($action) {
                    case 'clear_all':
                        $this->clearAllCache();
                        return success('所有缓存已清除');
                        
                    case 'clear_template':
                        $this->clearTemplateCache();
                        return success('模板缓存已清除');
                        
                    case 'clear_data':
                        $this->clearDataCache();
                        return success('数据缓存已清除');
                        
                    case 'get_stats':
                        $stats = $this->getCacheStats();
                        return json(['code' => 0, 'data' => $stats]);
                        
                    default:
                        return error('未知操作');
                }
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
        }
        
        return $this->view('dev/cache');
    }
    
    /**
     * 日志查看器
     * @return \Webman\Http\Response
     */
    public function logs()
    {
        if ($this->isPost()) {
            $action = $this->post['action'] ?? '';
            
            try {
                switch ($action) {
                    case 'list':
                        $logs = $this->getLogFiles();
                        return json(['code' => 0, 'data' => $logs]);
                        
                    case 'read':
                        $file = $this->post['file'] ?? '';
                        $lines = $this->post['lines'] ?? 100;
                        $content = $this->readLogFile($file, $lines);
                        return json(['code' => 0, 'data' => $content]);
                        
                    case 'search':
                        $file = $this->post['file'] ?? '';
                        $keyword = $this->post['keyword'] ?? '';
                        $content = $this->searchLogFile($file, $keyword);
                        return json(['code' => 0, 'data' => $content]);
                        
                    case 'clear':
                        $file = $this->post['file'] ?? '';
                        $this->clearLogFile($file);
                        return success('日志已清空');
                        
                    case 'download':
                        $file = $this->post['file'] ?? '';
                        return $this->downloadLogFile($file);
                        
                    default:
                        return error('未知操作');
                }
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
        }
        
        return $this->view('dev/logs');
    }
    
    /**
     * 数据库管理
     * @return \Webman\Http\Response
     */
    public function database()
    {
        if ($this->isPost()) {
            $action = $this->post['action'] ?? '';
            
            try {
                switch ($action) {
                    case 'tables':
                        $tables = $this->getDatabaseTables();
                        return json(['code' => 0, 'data' => $tables]);
                        
                    case 'structure':
                        $table = $this->post['table'] ?? '';
                        $structure = $this->getTableStructure($table);
                        return json(['code' => 0, 'data' => $structure]);
                        
                    case 'data':
                        $table = $this->post['table'] ?? '';
                        $page = $this->post['page'] ?? 1;
                        $limit = $this->post['limit'] ?? 20;
                        $data = $this->getTableData($table, $page, $limit);
                        return json(['code' => 0, 'data' => $data]);
                        
                    case 'execute':
                        $sql = $this->post['sql'] ?? '';
                        $result = $this->executeSql($sql);
                        return json(['code' => 0, 'data' => $result]);
                        
                    case 'optimize':
                        $table = $this->post['table'] ?? '';
                        $this->optimizeTable($table);
                        return success('表优化成功');
                        
                    default:
                        return error('未知操作');
                }
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
        }
        
        return $this->view('dev/database');
    }
    
    /**
     * 文件管理器
     * @return \Webman\Http\Response
     */
    public function fileManager()
    {
        if ($this->isPost()) {
            $action = $this->post['action'] ?? '';
            
            try {
                switch ($action) {
                    case 'list':
                        $path = $this->post['path'] ?? '';
                        $files = $this->getFileList($path);
                        return json(['code' => 0, 'data' => $files]);
                        
                    case 'read':
                        $file = $this->post['file'] ?? '';
                        $content = $this->readFile($file);
                        return json(['code' => 0, 'data' => $content]);
                        
                    case 'save':
                        $file = $this->post['file'] ?? '';
                        $content = $this->post['content'] ?? '';
                        $this->saveFile($file, $content);
                        return success('文件保存成功');
                        
                    case 'delete':
                        $path = $this->post['path'] ?? '';
                        $this->deleteFile($path);
                        return success('删除成功');
                        
                    case 'create_dir':
                        $path = $this->post['path'] ?? '';
                        $name = $this->post['name'] ?? '';
                        $this->createDirectory($path, $name);
                        return success('文件夹创建成功');
                        
                    case 'download':
                        $file = $this->post['file'] ?? '';
                        return $this->downloadFile($file);
                        
                    default:
                        return error('未知操作');
                }
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
        }
        
        return $this->view('dev/file_manager');
    }
    
    /**
     * Composer 管理
     * @return \Webman\Http\Response
     */
    public function composer()
    {
        if ($this->isPost()) {
            $action = $this->post['action'] ?? '';
            
            try {
                switch ($action) {
                    case 'installed':
                        $packages = $this->getInstalledPackages();
                        return json(['code' => 0, 'data' => $packages]);
                        
                    case 'search':
                        $keyword = $this->post['keyword'] ?? '';
                        $results = $this->searchPackages($keyword);
                        return json(['code' => 0, 'data' => $results]);
                        
                    case 'install':
                        $package = $this->post['package'] ?? '';
                        $this->installPackage($package);
                        return success('安装成功，请刷新页面查看');
                        
                    case 'update':
                        $package = $this->post['package'] ?? '';
                        $this->updatePackage($package);
                        return success('更新成功');
                        
                    case 'remove':
                        $package = $this->post['package'] ?? '';
                        $this->removePackage($package);
                        return success('删除成功');
                        
                    default:
                        return error('未知操作');
                }
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
        }
        
        return $this->view('dev/composer');
    }
    
    /**
     * 开发辅助工具
     * @return \Webman\Http\Response
     */
    public function tools()
    {
        return $this->view('dev/tools');
    }
    
    /**
     * 性能分析
     * @return \Webman\Http\Response
     */
    public function performance()
    {
        if ($this->isPost()) {
            $action = $this->post['action'] ?? '';
            
            try {
                switch ($action) {
                    case 'slow_queries':
                        $queries = $this->getSlowQueries();
                        return json(['code' => 0, 'data' => $queries]);
                        
                    case 'memory_usage':
                        $memory = $this->getMemoryUsage();
                        return json(['code' => 0, 'data' => $memory]);
                        
                    case 'opcache_status':
                        $opcache = $this->getOpcacheStatus();
                        return json(['code' => 0, 'data' => $opcache]);
                        
                    default:
                        return error('未知操作');
                }
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
        }
        
        return $this->view('dev/performance');
    }
    
    /**
     * 计划任务管理
     * @return \Webman\Http\Response
     */
    public function crontab()
    {
        if ($this->isPost()) {
            $action = $this->post['action'] ?? '';
            
            try {
                switch ($action) {
                    case 'bt_list':
                        $tasks = $this->getBaoTaCrontabList();
                        return json(['code' => 0, 'data' => $tasks]);
                        
                    case 'log':
                        $echo = $this->post['echo'] ?? '';
                        $log = $this->getBtTaskLog($echo);
                        return json(['code' => 0, 'data' => $log]);
                        
                    default:
                        return error('未知操作');
                }
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
        }
        
        return $this->view('dev/crontab');
    }
    
    /**
     * 获取宝塔计划任务列表
     */
    protected function getBaoTaCrontabList()
    {
        $dbFile = '/www/server/panel/data/db/crontab.db';
        
        if (!file_exists($dbFile)) {
            return [
                'error' => '宝塔计划任务数据库不存在',
                'tasks' => []
            ];
        }
        
        if (!class_exists('SQLite3')) {
            return [
                'error' => 'SQLite3 扩展未安装',
                'tasks' => []
            ];
        }
        
        try {
            $db = new \SQLite3($dbFile, SQLITE3_OPEN_READONLY);
            $result = $db->query("SELECT * FROM crontab ORDER BY id DESC");
            
            $tasks = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                // 构建宝塔风格的执行周期显示
                $cycle = $this->getBtCycleText($row);
                
                // 获取执行内容
                $command = '';
                if (!empty($row['sBody'])) {
                    $command = $row['sBody'];
                } elseif (!empty($row['urladdress'])) {
                    $command = '访问 URL: ' . $row['urladdress'];
                } else {
                    $command = $row['sName'] ?: '未知';
                }
                
                $tasks[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'type' => $this->getBtTaskType($row['type']),
                    'cycle' => $cycle,
                    'command' => $command,
                    'status' => $row['status'] == 1 ? '运行中' : '已禁用',
                    'disabled' => $row['status'] != 1,
                    'addtime' => $row['addtime'],
                    'echo' => $row['echo'],
                    'where1' => $row['where1'],
                    'where_hour' => $row['where_hour'],
                    'where_minute' => $row['where_minute']
                ];
            }
            
            $db->close();
            
            return [
                'error' => '',
                'tasks' => $tasks
            ];
        } catch (\Exception $e) {
            return [
                'error' => '读取宝塔数据库失败: ' . $e->getMessage(),
                'tasks' => []
            ];
        }
    }
    
    /**
     * 获取宝塔风格的执行周期文本
     */
    protected function getBtCycleText($row)
    {
        $type = $row['type'];
        $where1 = $row['where1'];
        $hour = $row['where_hour'];
        $minute = $row['where_minute'];
        
        switch ($type) {
            case 'day':
                return sprintf('每天 %02d:%02d', $hour, $minute);
            case 'hour':
                return sprintf('每小时 第%d分钟', $minute);
            case 'minute-n':
                return sprintf('每%d分钟', $where1);
            case 'week':
                $weeks = ['日', '一', '二', '三', '四', '五', '六'];
                $weekText = isset($weeks[$where1]) ? $weeks[$where1] : $where1;
                return sprintf('每周%s %02d:%02d', $weekText, $hour, $minute);
            case 'month':
                return sprintf('每月%d日 %02d:%02d', $where1, $hour, $minute);
            default:
                return sprintf('%02d:%02d', $hour, $minute);
        }
    }
    
    /**
     * 获取任务日志
     */
    protected function getBtTaskLog($echo)
    {
        $logFile = "/www/server/cron/{$echo}.log";
        
        if (!file_exists($logFile)) {
            return '暂无日志';
        }
        
        // 读取最后 1000 行
        $lines = shell_exec("tail -n 1000 {$logFile}");
        return $lines ?: '暂无日志';
    }
    
    /**
     * 获取宝塔任务类型名称
     */
    protected function getBtTaskType($type)
    {
        $types = [
            'day' => '每天',
            'day-n' => '每N天',
            'hour' => '每小时',
            'hour-n' => '每N小时',
            'minute-n' => '每N分钟',
            'week' => '每周',
            'month' => '每月'
        ];
        
        return $types[$type] ?? $type;
    }
    
    /**
     * 获取计划任务列表
     */
    /**
     * 获取慢查询
     */
    protected function getSlowQueries()
    {
        try {
            // 检查慢查询日志是否开启
            $slowLogStatus = \think\facade\Db::query("SHOW VARIABLES LIKE 'slow_query_log'");
            $slowLogFile = \think\facade\Db::query("SHOW VARIABLES LIKE 'slow_query_log_file'");
            $longQueryTime = \think\facade\Db::query("SHOW VARIABLES LIKE 'long_query_time'");
            
            $result = [
                'enabled' => isset($slowLogStatus[0]['Value']) && $slowLogStatus[0]['Value'] === 'ON',
                'log_file' => isset($slowLogFile[0]['Value']) ? $slowLogFile[0]['Value'] : '',
                'long_query_time' => isset($longQueryTime[0]['Value']) ? $longQueryTime[0]['Value'] : '10',
                'queries' => []
            ];
            
            // 如果开启了慢查询日志，尝试读取
            if ($result['enabled'] && file_exists($result['log_file'])) {
                $content = file_get_contents($result['log_file']);
                $lines = explode("\n", $content);
                $queries = [];
                $currentQuery = null;
                
                foreach ($lines as $line) {
                    if (strpos($line, '# Time:') === 0) {
                        if ($currentQuery) {
                            $queries[] = $currentQuery;
                        }
                        $currentQuery = ['time' => trim(substr($line, 7)), 'query' => ''];
                    } elseif ($currentQuery && strpos($line, '# Query_time:') === 0) {
                        preg_match('/Query_time: ([\d.]+)/', $line, $matches);
                        $currentQuery['query_time'] = isset($matches[1]) ? $matches[1] : '0';
                    } elseif ($currentQuery && $line && $line[0] !== '#') {
                        $currentQuery['query'] .= $line . ' ';
                    }
                }
                
                if ($currentQuery) {
                    $queries[] = $currentQuery;
                }
                
                $result['queries'] = array_slice(array_reverse($queries), 0, 20);
            }
            
            return $result;
        } catch (\Exception $e) {
            return [
                'enabled' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取内存使用情况
     */
    protected function getMemoryUsage()
    {
        return [
            'current' => memory_get_usage(true),
            'current_format' => $this->formatBytes(memory_get_usage(true)),
            'peak' => memory_get_peak_usage(true),
            'peak_format' => $this->formatBytes(memory_get_peak_usage(true)),
            'limit' => ini_get('memory_limit'),
            'real_usage' => memory_get_usage(false),
            'real_usage_format' => $this->formatBytes(memory_get_usage(false))
        ];
    }
    
    /**
     * 获取 OPcache 状态
     */
    protected function getOpcacheStatus()
    {
        if (!function_exists('opcache_get_status')) {
            return [
                'enabled' => false,
                'message' => 'OPcache 未安装或未启用'
            ];
        }
        
        $status = opcache_get_status(false);
        
        if (!$status) {
            return [
                'enabled' => false,
                'message' => 'OPcache 未启用'
            ];
        }
        
        return [
            'enabled' => true,
            'memory_usage' => [
                'used' => $status['memory_usage']['used_memory'],
                'used_format' => $this->formatBytes($status['memory_usage']['used_memory']),
                'free' => $status['memory_usage']['free_memory'],
                'free_format' => $this->formatBytes($status['memory_usage']['free_memory']),
                'wasted' => $status['memory_usage']['wasted_memory'],
                'wasted_format' => $this->formatBytes($status['memory_usage']['wasted_memory']),
                'usage_percent' => round($status['memory_usage']['current_wasted_percentage'], 2)
            ],
            'statistics' => [
                'num_cached_scripts' => $status['opcache_statistics']['num_cached_scripts'],
                'hits' => $status['opcache_statistics']['hits'],
                'misses' => $status['opcache_statistics']['misses'],
                'hit_rate' => round($status['opcache_statistics']['opcache_hit_rate'], 2)
            ]
        ];
    }
    
    /**
     * 获取已安装的包
     */
    protected function getInstalledPackages()
    {
        $composerLock = base_path() . '/composer.lock';
        
        if (!file_exists($composerLock)) {
            return [];
        }
        
        $lockData = json_decode(file_get_contents($composerLock), true);
        $packages = [];
        
        if (isset($lockData['packages'])) {
            foreach ($lockData['packages'] as $package) {
                $packages[] = [
                    'name' => $package['name'],
                    'version' => $package['version'],
                    'description' => $package['description'] ?? '',
                    'type' => $package['type'] ?? 'library',
                    'homepage' => $package['homepage'] ?? '',
                    'time' => $package['time'] ?? ''
                ];
            }
        }
        
        return $packages;
    }
    
    /**
     * 搜索包
     */
    protected function searchPackages($keyword)
    {
        if (empty($keyword)) {
            throw new \Exception('请输入搜索关键词');
        }
        
        // 使用 Packagist API 搜索
        $url = "https://packagist.org/search.json?q=" . urlencode($keyword);
        
        $result = curl_request($url, [], 'GET', [], false, 10);
        
        if ($result['code'] !== 200 || empty($result['data'])) {
            throw new \Exception('搜索失败，请检查网络连接');
        }
        
        // data 已经自动转换为数组
        $data = $result['data'];
        
        if (!isset($data['results'])) {
            return [];
        }
        
        $results = [];
        foreach ($data['results'] as $item) {
            $results[] = [
                'name' => $item['name'],
                'description' => $item['description'] ?? '',
                'downloads' => $item['downloads'] ?? 0,
                'favers' => $item['favers'] ?? 0,
                'repository' => $item['repository'] ?? ''
            ];
        }
        
        return array_slice($results, 0, 20); // 最多返回20个结果
    }
    
    /**
     * 安装包
     */
    protected function installPackage($package)
    {
        if (empty($package)) {
            throw new \Exception('请指定要安装的包');
        }
        
        $basePath = base_path();
        $command = "cd {$basePath} && composer require {$package} 2>&1";
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception('安装失败: ' . implode("\n", $output));
        }
    }
    
    /**
     * 更新包
     */
    protected function updatePackage($package)
    {
        if (empty($package)) {
            throw new \Exception('请指定要更新的包');
        }
        
        $basePath = base_path();
        $command = "cd {$basePath} && composer update {$package} 2>&1";
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception('更新失败: ' . implode("\n", $output));
        }
    }
    
    /**
     * 删除包
     */
    protected function removePackage($package)
    {
        if (empty($package)) {
            throw new \Exception('请指定要删除的包');
        }
        
        $basePath = base_path();
        $command = "cd {$basePath} && composer remove {$package} 2>&1";
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception('删除失败: ' . implode("\n", $output));
        }
    }
    
    /**
     * 获取文件列表
     */
    protected function getFileList($path)
    {
        $basePath = base_path();
        $fullPath = $basePath . '/' . ltrim($path, '/');
        
        // 安全检查
        if (!$this->isPathSafe($fullPath, $basePath)) {
            throw new \Exception('非法路径');
        }
        
        if (!is_dir($fullPath)) {
            throw new \Exception('目录不存在');
        }
        
        $files = scandir($fullPath);
        $result = [];
        
        foreach ($files as $file) {
            if ($file == '.') {
                continue;
            }
            
            $filePath = $fullPath . '/' . $file;
            $relativePath = $path . '/' . $file;
            
            $item = [
                'name' => $file,
                'path' => ltrim($relativePath, '/'),
                'is_dir' => is_dir($filePath),
                'size' => is_file($filePath) ? filesize($filePath) : 0,
                'size_format' => is_file($filePath) ? $this->formatBytes(filesize($filePath)) : '-',
                'mtime' => filemtime($filePath),
                'mtime_format' => date('Y-m-d H:i:s', filemtime($filePath)),
                'readable' => is_readable($filePath),
                'writable' => is_writable($filePath),
                'can_edit' => $this->canEditFile(ltrim($relativePath, '/')),
                'can_delete' => $this->canDeleteFile(ltrim($relativePath, '/'))
            ];
            
            $result[] = $item;
        }
        
        // 排序：文件夹在前，文件在后
        usort($result, function($a, $b) {
            if ($a['is_dir'] && !$b['is_dir']) return -1;
            if (!$a['is_dir'] && $b['is_dir']) return 1;
            return strcmp($a['name'], $b['name']);
        });
        
        return [
            'current_path' => $path,
            'files' => $result
        ];
    }
    
    /**
     * 检查文件是否可以编辑
     */
    protected function canEditFile($path)
    {
        // 禁止编辑的目录
        $protectedDirs = [
            'vendor/',
            'runtime/',
            '.git/',
            'node_modules/'
        ];
        
        foreach ($protectedDirs as $dir) {
            if (strpos($path, $dir) === 0) {
                return false;
            }
        }
        
        // 禁止编辑的文件
        $protectedFiles = [
            'composer.json',
            'composer.lock',
            'package.json',
            'package-lock.json',
            '.env',
            '.gitignore'
        ];
        
        $fileName = basename($path);
        if (in_array($fileName, $protectedFiles)) {
            return false;
        }
        
        // 只允许编辑文本文件
        $allowedExtensions = [
            'php', 'html', 'css', 'js', 'json', 'xml', 'txt', 'md',
            'yml', 'yaml', 'ini', 'conf', 'sql', 'log'
        ];
        
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 检查文件是否可以删除
     */
    protected function canDeleteFile($path)
    {
        // 禁止删除的目录
        $protectedDirs = [
            'app',
            'config',
            'vendor',
            'public',
            'process',
            'support',
            '.git'
        ];
        
        // 获取第一级目录
        $firstDir = explode('/', $path)[0];
        
        if (in_array($firstDir, $protectedDirs)) {
            return false;
        }
        
        // 禁止删除的文件
        $protectedFiles = [
            'composer.json',
            'composer.lock',
            'start.php',
            '.env',
            '.gitignore',
            'README.md'
        ];
        
        $fileName = basename($path);
        if (in_array($fileName, $protectedFiles)) {
            return false;
        }
        
        // 如果是 .. 返回上级，不能删除
        if ($fileName === '..') {
            return false;
        }
        
        return true;
    }
    
    /**
     * 读取文件
     */
    protected function readFile($file)
    {
        $basePath = base_path();
        $fullPath = $basePath . '/' . ltrim($file, '/');
        
        // 安全检查
        if (!$this->isPathSafe($fullPath, $basePath)) {
            throw new \Exception('非法路径');
        }
        
        if (!file_exists($fullPath)) {
            throw new \Exception('文件不存在');
        }
        
        if (!is_file($fullPath)) {
            throw new \Exception('不是文件');
        }
        
        if (!is_readable($fullPath)) {
            throw new \Exception('文件不可读');
        }
        
        // 检查是否可以编辑
        if (!$this->canEditFile($file)) {
            throw new \Exception('该文件不允许编辑');
        }
        
        // 检查文件大小（限制 1MB）
        if (filesize($fullPath) > 1024 * 1024) {
            throw new \Exception('文件太大，无法在线编辑（限制 1MB）');
        }
        
        $content = file_get_contents($fullPath);
        
        return [
            'file' => $file,
            'content' => $content,
            'size' => filesize($fullPath),
            'writable' => is_writable($fullPath)
        ];
    }
    
    /**
     * 保存文件
     */
    protected function saveFile($file, $content)
    {
        $basePath = base_path();
        $fullPath = $basePath . '/' . ltrim($file, '/');
        
        // 安全检查
        if (!$this->isPathSafe($fullPath, $basePath)) {
            throw new \Exception('非法路径');
        }
        
        if (!file_exists($fullPath)) {
            throw new \Exception('文件不存在');
        }
        
        if (!is_writable($fullPath)) {
            throw new \Exception('文件不可写');
        }
        
        // 检查是否可以编辑
        if (!$this->canEditFile($file)) {
            throw new \Exception('该文件不允许编辑');
        }
        
        file_put_contents($fullPath, $content);
    }
    
    /**
     * 删除文件或文件夹
     */
    protected function deleteFile($path)
    {
        $basePath = base_path();
        $fullPath = $basePath . '/' . ltrim($path, '/');
        
        // 安全检查
        if (!$this->isPathSafe($fullPath, $basePath)) {
            throw new \Exception('非法路径');
        }
        
        if (!file_exists($fullPath)) {
            throw new \Exception('文件或文件夹不存在');
        }
        
        // 检查是否可以删除
        if (!$this->canDeleteFile($path)) {
            throw new \Exception('该文件或文件夹不允许删除');
        }
        
        if (is_file($fullPath)) {
            unlink($fullPath);
        } else {
            $this->deleteDirectory($fullPath);
        }
    }
    
    /**
     * 递归删除目录
     */
    protected function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * 创建文件夹
     */
    protected function createDirectory($path, $name)
    {
        $basePath = base_path();
        $fullPath = $basePath . '/' . ltrim($path, '/') . '/' . $name;
        
        // 安全检查
        if (!$this->isPathSafe($fullPath, $basePath)) {
            throw new \Exception('非法路径');
        }
        
        if (file_exists($fullPath)) {
            throw new \Exception('文件夹已存在');
        }
        
        mkdir($fullPath, 0755, true);
    }
    
    /**
     * 下载文件
     */
    protected function downloadFile($file)
    {
        $basePath = base_path();
        $fullPath = $basePath . '/' . ltrim($file, '/');
        
        // 安全检查
        if (!$this->isPathSafe($fullPath, $basePath)) {
            throw new \Exception('非法路径');
        }
        
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            throw new \Exception('文件不存在');
        }
        
        return response()->download($fullPath, basename($file));
    }
    
    /**
     * 检查路径是否安全
     */
    protected function isPathSafe($path, $basePath)
    {
        $realPath = realpath($path);
        $realBasePath = realpath($basePath);
        
        // 如果路径不存在，检查父目录
        if ($realPath === false) {
            $realPath = realpath(dirname($path));
        }
        
        if ($realPath === false) {
            return false;
        }
        
        return strpos($realPath, $realBasePath) === 0;
    }
    
    /**
     * 获取数据库表列表
     */
    protected function getDatabaseTables()
    {
        $tables = \think\facade\Db::query('SHOW TABLE STATUS');
        
        $result = [];
        foreach ($tables as $table) {
            $result[] = [
                'name' => $table['Name'],
                'engine' => $table['Engine'],
                'rows' => $table['Rows'],
                'data_length' => $table['Data_length'],
                'index_length' => $table['Index_length'],
                'total_length' => $table['Data_length'] + $table['Index_length'],
                'size_format' => $this->formatBytes($table['Data_length'] + $table['Index_length']),
                'comment' => $table['Comment']
            ];
        }
        
        return $result;
    }
    
    /**
     * 获取表结构
     */
    protected function getTableStructure($table)
    {
        if (empty($table)) {
            throw new \Exception('请指定表名');
        }
        
        // 获取字段信息
        $columns = \think\facade\Db::query("SHOW FULL COLUMNS FROM `{$table}`");
        
        // 获取索引信息
        $indexes = \think\facade\Db::query("SHOW INDEX FROM `{$table}`");
        
        // 获取建表语句
        $createTable = \think\facade\Db::query("SHOW CREATE TABLE `{$table}`");
        
        return [
            'columns' => $columns,
            'indexes' => $indexes,
            'create_sql' => $createTable[0]['Create Table'] ?? ''
        ];
    }
    
    /**
     * 获取表数据
     */
    protected function getTableData($table, $page = 1, $limit = 20)
    {
        if (empty($table)) {
            throw new \Exception('请指定表名');
        }
        
        $offset = ($page - 1) * $limit;
        
        // 获取总数
        $total = \think\facade\Db::query("SELECT COUNT(*) as count FROM `{$table}`");
        $count = $total[0]['count'] ?? 0;
        
        // 获取数据
        $data = \think\facade\Db::query("SELECT * FROM `{$table}` LIMIT {$offset}, {$limit}");
        
        return [
            'total' => $count,
            'page' => $page,
            'limit' => $limit,
            'data' => $data
        ];
    }
    
    /**
     * 执行 SQL（只读模式）
     */
    protected function executeSql($sql)
    {
        if (empty($sql)) {
            throw new \Exception('请输入 SQL 语句');
        }
        
        // 只允许 SELECT 查询
        $sql = trim($sql);
        if (!preg_match('/^SELECT/i', $sql)) {
            throw new \Exception('安全限制：只允许执行 SELECT 查询');
        }
        
        $result = \think\facade\Db::query($sql);
        
        return [
            'sql' => $sql,
            'rows' => count($result),
            'data' => $result
        ];
    }
    
    /**
     * 优化表
     */
    protected function optimizeTable($table)
    {
        if (empty($table)) {
            throw new \Exception('请指定表名');
        }
        
        \think\facade\Db::execute("OPTIMIZE TABLE `{$table}`");
    }
    
    /**
     * 获取日志文件列表
     */
    protected function getLogFiles()
    {
        $logPath = runtime_path() . '/logs';
        
        if (!is_dir($logPath)) {
            return [];
        }
        
        $files = scandir($logPath);
        $logs = [];
        
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            
            $filePath = $logPath . '/' . $file;
            
            if (is_file($filePath)) {
                $logs[] = [
                    'name' => $file,
                    'size' => filesize($filePath),
                    'size_format' => $this->formatBytes(filesize($filePath)),
                    'mtime' => filemtime($filePath),
                    'mtime_format' => date('Y-m-d H:i:s', filemtime($filePath))
                ];
            }
        }
        
        // 按修改时间倒序
        usort($logs, function($a, $b) {
            return $b['mtime'] - $a['mtime'];
        });
        
        return $logs;
    }
    
    /**
     * 读取日志文件
     */
    protected function readLogFile($file, $lines = 100)
    {
        $logPath = runtime_path() . '/logs/' . $file;
        
        if (!file_exists($logPath)) {
            throw new \Exception('日志文件不存在');
        }
        
        // 读取最后 N 行
        $content = $this->tailFile($logPath, $lines);
        
        return [
            'file' => $file,
            'content' => $content,
            'lines' => count(explode("\n", $content))
        ];
    }
    
    /**
     * 搜索日志文件
     */
    protected function searchLogFile($file, $keyword)
    {
        $logPath = runtime_path() . '/logs/' . $file;
        
        if (!file_exists($logPath)) {
            throw new \Exception('日志文件不存在');
        }
        
        $content = file_get_contents($logPath);
        $lines = explode("\n", $content);
        $result = [];
        
        foreach ($lines as $index => $line) {
            if (stripos($line, $keyword) !== false) {
                $result[] = [
                    'line' => $index + 1,
                    'content' => $line
                ];
            }
        }
        
        return [
            'file' => $file,
            'keyword' => $keyword,
            'total' => count($result),
            'results' => array_slice($result, 0, 500) // 最多返回500条
        ];
    }
    
    /**
     * 清空日志文件
     */
    protected function clearLogFile($file)
    {
        $logPath = runtime_path() . '/logs/' . $file;
        
        if (!file_exists($logPath)) {
            throw new \Exception('日志文件不存在');
        }
        
        file_put_contents($logPath, '');
    }
    
    /**
     * 下载日志文件
     */
    protected function downloadLogFile($file)
    {
        $logPath = runtime_path() . '/logs/' . $file;
        
        if (!file_exists($logPath)) {
            throw new \Exception('日志文件不存在');
        }
        
        return response()->download($logPath, $file);
    }
    
    /**
     * 读取文件最后 N 行
     */
    protected function tailFile($file, $lines = 100)
    {
        $fp = fopen($file, 'r');
        $pos = -2;
        $eof = '';
        $str = '';
        $lineCount = 0;
        
        fseek($fp, $pos, SEEK_END);
        
        while ($lineCount <= $lines) {
            $char = fgetc($fp);
            
            if ($char === "\n") {
                $lineCount++;
            }
            
            $str = $char . $str;
            $pos--;
            
            if (fseek($fp, $pos, SEEK_END) === -1) {
                rewind($fp);
                $str = fread($fp, -$pos) . $str;
                break;
            }
        }
        
        fclose($fp);
        
        return trim($str);
    }
    
    /**
     * 清除所有缓存
     */
    protected function clearAllCache()
    {
        $this->clearTemplateCache();
        $this->clearDataCache();
    }
    
    /**
     * 清除模板缓存
     */
    protected function clearTemplateCache()
    {
        $runtimePath = runtime_path();
        
        // 清除 think 模板缓存
        $tempPath = $runtimePath . '/temp';
        if (is_dir($tempPath)) {
            $this->deleteDir($tempPath);
        }
        
        // 清除 view 编译缓存
        $viewPath = $runtimePath . '/view';
        if (is_dir($viewPath)) {
            $this->deleteDir($viewPath);
        }
    }
    
    /**
     * 清除数据缓存
     */
    protected function clearDataCache()
    {
        $runtimePath = runtime_path();
        
        // 清除 cache 目录
        $cachePath = $runtimePath . '/cache';
        if (is_dir($cachePath)) {
            $this->deleteDir($cachePath);
        }
        
        // 清除 webman think-cache
        try {
            \support\think\Cache::clear();
        } catch (\Exception $e) {
            // 忽略错误
        }
    }
    
    /**
     * 递归删除目录
     */
    protected function deleteDir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                @unlink($path);
            }
        }
        
        // 不删除目录本身，只清空内容
    }
    
    /**
     * 获取缓存统计
     */
    protected function getCacheStats()
    {
        $runtimePath = runtime_path();
        
        $stats = [
            'template' => $this->getDirSize($runtimePath . '/temp') + $this->getDirSize($runtimePath . '/view'),
            'data' => $this->getDirSize($runtimePath . '/cache'),
            'total' => 0
        ];
        
        $stats['total'] = $stats['template'] + $stats['data'];
        
        // 格式化大小
        $stats['template_format'] = $this->formatBytes($stats['template']);
        $stats['data_format'] = $this->formatBytes($stats['data']);
        $stats['total_format'] = $this->formatBytes($stats['total']);
        
        return $stats;
    }
    
    /**
     * 获取目录大小
     */
    protected function getDirSize($dir)
    {
        if (!is_dir($dir)) {
            return 0;
        }
        
        $size = 0;
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $size += $this->getDirSize($path);
            } else {
                $size += filesize($path);
            }
        }
        
        return $size;
    }
    
    /**
     * 格式化字节
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
}

