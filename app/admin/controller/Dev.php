<?php
namespace app\admin\controller;

use app\admin\service\FrameworkUpgradeService;

/**
 * 开发者工具控制器
 */
class Dev extends Base
{
    /**
     * 系统框架升级
     */
    public function upgrade()
    {
        $service = new FrameworkUpgradeService();

        if ($this->isPost()) {
            $action = $this->post['action'] ?? '';
            try {
                if ($action === 'check') {
                    $info = $service->checkUpgrade();
                    return json(['code' => 0, 'data' => $info]);
                }
                if ($action === 'do_upgrade') {
                    $downloadUrl = $this->post['download_url'] ?? '';
                    $newVersion  = $this->post['new_version']  ?? '';
                    if (!$downloadUrl || !$newVersion) {
                        return error('参数错误');
                    }
                    $log = $service->upgrade($downloadUrl, $newVersion);
                    return json(['code' => 1, 'msg' => '升级成功', 'data' => ['log' => $log]]);
                }
                return error('未知操作');
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
        }

        $currentVersion = $service->currentVersion();
        return $this->view(['current_version' => $currentVersion]);
    }

    /**
     * 表单构建器
     */
    public function formBuild()
    {
        return $this->view();
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
        
        return $this->view();
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
        
        return $this->view();
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
        
        return $this->view();
    }
    
    /**
     * 文件管理器
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
        
        return $this->view();
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

