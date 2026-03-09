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
    
    /**
     * 配置分组列表
     * @return \Webman\Http\Response
     */
    public function groupIndex()
    {
        $list = ConfigGroupModel::order('sort asc, id desc')
            ->paginate(20);
        
        return $this->view('dev/configgroup/index', [
            'list' => $list
        ]);
    }
    
    /**
     * 添加配置分组
     * @return \Webman\Http\Response
     */
    public function groupAdd()
    {
        if ($this->isPost()) {
            try {
                validate([
                    'group_key|分组标识'     => 'require',
                    'group_title|分组标题'   => 'require',
                ])->check($this->post);
                
                // 检查分组标识是否已存在
                $exists = ConfigGroupModel::where('group_key', $this->post['group_key'])->find();
                if ($exists) {
                    throw new \Exception('分组标识已存在');
                }
                
                ConfigGroupModel::create($this->post);
                
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '添加失败');
            }
            
            return success('添加成功', 'groupIndex');
        }
        
        return $this->view('dev/configgroup/add');
    }
    
    /**
     * 编辑配置分组
     * @return \Webman\Http\Response
     */
    public function groupEdit()
    {
        $id = $this->get['id'] ?? 0;
        $group = ConfigGroupModel::find($id);
        
        if (!$group) {
            return error('分组不存在');
        }
        
        if ($this->isPost()) {
            try {
                validate([
                    'group_key|分组标识'     => 'require',
                    'group_title|分组标题'   => 'require',
                ])->check($this->post);
                
                // 检查分组标识是否已被其他记录使用
                $exists = ConfigGroupModel::where('group_key', $this->post['group_key'])
                    ->where('id', '<>', $id)
                    ->find();
                if ($exists) {
                    throw new \Exception('分组标识已存在');
                }
                
                $group->save($this->post);
                
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '编辑失败');
            }
            
            return success('编辑成功', 'groupIndex');
        }
        
        return $this->view('dev/configgroup/edit', [
            'group' => $group
        ]);
    }
    
    /**
     * 删除配置分组
     * @return \Webman\Http\Response
     */
    public function groupDelete()
    {
        if (!$this->isPost()) {
            return error('非法请求');
        }
        
        $id = $this->post['id'] ?? 0;
        
        try {
            $group = ConfigGroupModel::find($id);
            if (!$group) {
                throw new \Exception('分组不存在');
            }
            
            // 检查是否有配置项
            $count = ConfigModel::where('group_key', $group['group_key'])->count();
            
            if ($count > 0) {
                throw new \Exception('该分组下还有配置项，无法删除');
            }
            
            $group->delete();
            
            return success('删除成功');
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
    }
    
    /**
     * 配置项列表
     * @return \Webman\Http\Response
     */
    public function configManage()
    {
        // 从 URL 获取分组
        $groupKey = $this->get['group'] ?? '';
        
        if ($this->isPost()) {
            $page = $this->post['page'] ?? 1;
            $limit = $this->post['limit'] ?? 15;
            
            $where = [];
            
            if (!empty($this->post['group_key'])) {
                $where[] = ['group_key', 'like', '%' . $this->post['group_key'] . '%'];
            }
            
            if (!empty($this->post['config_key'])) {
                $where[] = ['config_key', 'like', '%' . $this->post['config_key'] . '%'];
            }
            
            $list = ConfigModel::where($where)
                ->order('group_key asc, sort asc, id desc')
                ->paginate([
                    'list_rows' => $limit,
                    'page' => $page
                ]);
            
            return json([
                'code' => 0,
                'msg' => '',
                'count' => $list->total(),
                'data' => $list->items()
            ]);
        }
        
        // GET 请求，返回初始数据
        $where = [];
        
        // 必须指定分组
        if (empty($groupKey)) {
            return error('请先选择配置分组');
        }
        
        $where[] = ['group_key', '=', $groupKey];
        
        $list = ConfigModel::where($where)
            ->order('sort asc, id desc')
            ->paginate(20);
        
        // 获取分组信息
        $group = ConfigGroupModel::where('group_key', $groupKey)->find();
        
        if (!$group) {
            return error('配置分组不存在');
        }
        
        return $this->view('dev/config_manage', [
            'groupKey' => $groupKey,
            'group' => $group,
            'list' => $list
        ]);
    }
    
    /**
     * 添加配置项
     * @return \Webman\Http\Response
     */
    public function configAdd()
    {
        // 从 URL 获取分组
        $groupKey = $this->get['group'] ?? '';
        
        if (empty($groupKey)) {
            return error('请先选择配置分组');
        }
        
        // 获取分组信息
        $group = ConfigGroupModel::where('group_key', $groupKey)->find();
        
        if (!$group) {
            return error('配置分组不存在');
        }
        
        if ($this->isPost()) {
            try {
                validate([
                    'config_key|配置项标识'     => 'require',
                    'config_title|配置项标题'   => 'require',
                ])->check($this->post);
                
                // 检查配置项标识是否已存在
                $exists = ConfigModel::where('config_key', $this->post['config_key'])->find();
                if ($exists) {
                    throw new \Exception('配置项标识已存在');
                }
                
                $data = $this->post;
                
                // 设置分组
                $data['group_key'] = $groupKey;
                $data['group_title'] = $group['group_title'];
                
                // 处理配置选项
                if (!empty($data['config_options'])) {
                    if (is_string($data['config_options'])) {
                        // 验证 JSON 格式
                        $options = json_decode($data['config_options'], true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new \Exception('配置选项格式错误，请输入正确的 JSON 格式');
                        }
                    }
                }
                
                ConfigModel::create($data);
                
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '添加失败');
            }
            
            return success('添加成功', 'configManage?group=' . $groupKey);
        }
        
        return $this->view('dev/config_add', [
            'group' => $group
        ]);
    }
    
    /**
     * 编辑配置项
     * @return \Webman\Http\Response
     */
    public function configEdit()
    {
        $id = $this->get['id'] ?? 0;
        $config = ConfigModel::find($id);
        
        if (!$config) {
            return error('配置项不存在');
        }
        
        $groupKey = $config['group_key'];
        
        if ($this->isPost()) {
            try {
                validate([
                    'config_key|配置项标识'     => 'require',
                    'config_title|配置项标题'   => 'require',
                ])->check($this->post);
                
                // 检查配置项标识是否已被其他记录使用
                $exists = ConfigModel::where('config_key', $this->post['config_key'])
                    ->where('id', '<>', $id)
                    ->find();
                if ($exists) {
                    throw new \Exception('配置项标识已存在');
                }
                
                $data = $this->post;
                
                // 保持原分组
                $data['group_key'] = $groupKey;
                
                // 处理配置选项
                if (!empty($data['config_options'])) {
                    if (is_string($data['config_options'])) {
                        // 验证 JSON 格式
                        $options = json_decode($data['config_options'], true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new \Exception('配置选项格式错误，请输入正确的 JSON 格式');
                        }
                    }
                }
                
                $config->save($data);
                
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '编辑失败');
            }
            
            return success('编辑成功', 'configManage?group=' . $groupKey);
        }
        
        // 获取分组信息
        $group = ConfigGroupModel::where('group_key', $groupKey)->find();
        
        // 处理 config_options 显示
        if (!empty($config['config_options'])) {
            if (is_array($config['config_options'])) {
                $config['config_options'] = json_encode($config['config_options'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }
        }
        
        return $this->view('dev/config_edit', [
            'config' => $config,
            'group' => $group
        ]);
    }
    
    /**
     * 删除配置项
     * @return \Webman\Http\Response
     */
    public function configDelete()
    {
        if (!$this->isPost()) {
            return error('非法请求');
        }
        
        $id = $this->post['id'] ?? 0;
        
        try {
            $config = ConfigModel::find($id);
            if (!$config) {
                return error('配置项不存在');
            }
            
            $config->delete();
            
            return success('删除成功');
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
    }
}
