<?php
namespace app\admin\controller;

use app\admin\model\Plugin as PluginModel;
use support\Request;
use support\Response;

/**
 * 插件管理控制器
 */
class Plugin extends Base
{
    /**
     * 无需登录的方法
     */
    protected $noNeedLogin = [];
    
    /**
     * 无需鉴权的方法
     */
    protected $noNeedAuth = [];
    
    /**
     * 插件列表
     */
    public function index(): Response
    {
        // 获取数据库中的插件
        $dbPlugins = PluginModel::order('id desc')->column('*', 'identifier');
        
        // 扫描目录获取所有插件
        $scanPlugins = PluginModel::scanPlugins();
        
        // 合并数据
        $plugins = [];
        foreach ($scanPlugins as $plugin) {
            $identifier = $plugin['identifier'];
            if (isset($dbPlugins[$identifier])) {
                // 数据库中有记录，合并数据
                $plugin = array_merge($plugin, $dbPlugins[$identifier]->toArray());
                $plugin['in_db'] = true;
            } else {
                $plugin['in_db'] = false;
                $plugin['status'] = 0;
                $plugin['is_installed'] = 0;
            }
            $plugins[] = $plugin;
        }
        
        return $this->view('', ['plugins' => $plugins]);
    }
    
    /**
     * 上传插件
     */
    public function upload(): Response
    {
        if ($this->isPost()) {
            try {
                $file = $this->request->file('file');
                
                if (!$file) {
                    throw new \Exception('请选择文件');
                }
                
                // 验证文件类型
                $ext = strtolower($file->getUploadExtension());
                if ($ext !== 'zip') {
                    throw new \Exception('只支持 zip 格式的插件包');
                }
                
                // 创建临时目录
                $tempDir = runtime_path() . '/plugin_temp_' . uniqid();
                mkdir($tempDir, 0755, true);
                
                // 保存并解压
                $zipPath = $tempDir . '/plugin.zip';
                $file->move($zipPath);
                
                $zip = new \ZipArchive();
                if ($zip->open($zipPath) !== true) {
                    throw new \Exception('无法打开压缩包');
                }
                $zip->extractTo($tempDir);
                $zip->close();
                
                // 查找插件目录（解压后可能有一层目录）
                $pluginDir = $this->findPluginDir($tempDir);
                if (!$pluginDir) {
                    throw new \Exception('插件目录结构不正确');
                }
                
                // 读取插件配置
                $configFile = $pluginDir . '/config/plugin.php';
                if (!file_exists($configFile)) {
                    throw new \Exception('缺少插件配置文件 config/plugin.php');
                }
                
                $config = include $configFile;
                $identifier = $config['identifier'] ?? basename($pluginDir);
                
                // 检查插件是否已存在
                if (is_dir(plugin_path($identifier))) {
                    throw new \Exception('插件已存在，请先卸载旧版本');
                }
                
                // 移动到插件目录
                $targetDir = plugin_path($identifier);
                rename($pluginDir, $targetDir);
                
                // 清理临时目录
                $this->removeDir($tempDir);
                
                // 创建数据库记录（未安装状态）
                PluginModel::create([
                    'identifier'  => $identifier,
                    'name'        => $config['name'] ?? $identifier,
                    'version'     => $config['version'] ?? '1.0.0',
                    'author'      => $config['author'] ?? '',
                    'description' => $config['description'] ?? '',
                    'icon'        => $config['icon'] ?? 'mdi mdi-puzzle',
                    'status'      => 0,
                    'is_installed'=> 0,
                ]);
                
                return success('插件上传成功，请前往安装');
                
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '上传失败');
            }
        }
        
        return $this->view();
    }
    
    /**
     * 安装插件
     */
    public function install(): Response
    {
        if ($this->isPost()) {
            try {
                $id = $this->post['id'] ?? 0;
                $plugin = PluginModel::find($id);
                
                if (!$plugin) {
                    throw new \Exception('插件不存在');
                }
                
                if ($plugin->is_installed) {
                    throw new \Exception('插件已安装');
                }
                
                // 检查插件目录是否存在
                if (!is_dir(plugin_path($plugin->identifier))) {
                    throw new \Exception('插件文件不存在，请重新上传');
                }
                
                $plugin->install();
                
                return success('插件安装成功');
                
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '安装失败');
            }
        }
        
        // 显示安装页面（列出未安装的插件）
        $plugins = PluginModel::where('is_installed', 0)->select();
        return $this->view('', ['plugins' => $plugins]);
    }
    
    /**
     * 卸载插件
     */
    public function uninstall(): Response
    {
        if ($this->isPost()) {
            try {
                $id = $this->post['id'] ?? 0;
                $plugin = PluginModel::find($id);
                
                if (!$plugin) {
                    throw new \Exception('插件不存在');
                }
                
                if (!$plugin->is_installed) {
                    throw new \Exception('插件未安装');
                }
                
                $plugin->uninstall();
                
                return success('插件已卸载（数据保留）');
                
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '卸载失败');
            }
        }
        
        return error('非法请求');
    }
    
    /**
     * 启用/停用插件
     */
    public function toggle(): Response
    {
        if ($this->isPost()) {
            try {
                $id = $this->post['id'] ?? 0;
                $plugin = PluginModel::find($id);
                
                if (!$plugin) {
                    throw new \Exception('插件不存在');
                }
                
                if (!$plugin->is_installed) {
                    throw new \Exception('插件未安装，无法启用');
                }
                
                $plugin->toggle();
                $statusText = $plugin->status ? '启用' : '停用';
                
                return success("插件已{$statusText}");
                
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '操作失败');
            }
        }
        
        return error('非法请求');
    }
    
    /**
     * 删除插件（彻底删除文件）
     */
    public function delete(): Response
    {
        if ($this->isPost()) {
            try {
                $id = $this->post['id'] ?? 0;
                $plugin = PluginModel::find($id);
                
                if (!$plugin) {
                    throw new \Exception('插件不存在');
                }
                
                if ($plugin->is_installed) {
                    throw new \Exception('请先卸载插件再删除');
                }
                
                // 删除插件目录
                $pluginDir = plugin_path($plugin->identifier);
                if (is_dir($pluginDir)) {
                    $this->removeDir($pluginDir);
                }
                
                // 删除数据库记录
                $plugin->delete();
                
                return success('插件已删除');
                
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '删除失败');
            }
        }
        
        return error('非法请求');
    }
    
    /**
     * 插件配置
     */
    public function config(): Response
    {
        $id = $this->get['id'] ?? 0;
        $plugin = PluginModel::find($id);
        
        if (!$plugin) {
            return error('插件不存在');
        }
        
        if ($this->isPost()) {
            try {
                $config = $this->post['config'] ?? [];
                $plugin->config = json_encode($config);
                $plugin->save();
                
                return success('配置保存成功');
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '保存失败');
            }
        }
        
        // 读取配置文件中的默认配置
        $defaultConfig = PluginModel::getPluginConfig($plugin->identifier);
        $currentConfig = json_decode($plugin->config ?? '{}', true);
        
        return $this->view('', [
            'plugin'  => $plugin,
            'config'  => array_merge($defaultConfig['settings'] ?? [], $currentConfig),
        ]);
    }
    
    /**
     * 查找插件目录
     */
    protected function findPluginDir(string $dir): ?string
    {
        // 直接检查当前目录
        if (file_exists($dir . '/config/plugin.php')) {
            return $dir;
        }
        
        // 检查子目录
        $subDirs = glob($dir . '/*', GLOB_ONLYDIR);
        foreach ($subDirs as $subDir) {
            if (file_exists($subDir . '/config/plugin.php')) {
                return $subDir;
            }
        }
        
        return null;
    }
    
    /**
     * 递归删除目录
     */
    protected function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
