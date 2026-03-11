<?php
namespace app\admin\controller;

use app\admin\model\Plugin as PluginModel;
use app\admin\service\PluginService;

/**
 * 插件管理控制器
 */
class Plugin extends Base
{
    protected $noNeedAuth = ['index'];

    protected PluginService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new PluginService();
    }

    /**
     * 插件列表
     */
    public function index()
    {
        // 先扫描目录，发现新插件
        $this->service->scan();

        $plugins = PluginModel::order('id asc')->select()->toArray();

        // 递归从菜单树中取第一个有 href 的菜单项
        $findFirstHref = function(array $menus) use (&$findFirstHref): string {
            foreach ($menus as $m) {
                if (!empty($m['href'])) return $m['href'];
                $found = $findFirstHref($m['children'] ?? []);
                if ($found) return $found;
            }
            return '';
        };

        // 从各插件 config/app.php 读取 admin_entry，没有则从 menu.php 递归取第一个有 href 的菜单
        foreach ($plugins as &$p) {
            $appFile  = base_path("plugin/{$p['identifier']}/config/app.php");
            $menuFile = base_path("plugin/{$p['identifier']}/config/menu.php");
            $entry = '';
            if (file_exists($appFile)) {
                $cfg   = (function($f) { return include $f; })($appFile);
                $entry = (is_array($cfg) ? $cfg['admin_entry'] ?? '' : '');
            }
            if (!$entry && file_exists($menuFile)) {
                $menu  = (function($f) { return include $f; })($menuFile);
                $entry = is_array($menu) ? $findFirstHref($menu) : '';
            }
            $p['admin_entry'] = $entry;

            // 对比文件版本与数据库版本，判断是否可升级
            $fileVersion = '';
            if (file_exists($appFile)) {
                $cfg = (function($f) { return include $f; })($appFile);
                $fileVersion = is_array($cfg) ? ($cfg['version'] ?? '') : '';
            }
            $p['upgradable'] = $p['installed'] == 1 && $fileVersion && version_compare($fileVersion, $p['version'], '>');
            $p['file_version'] = $fileVersion;
        }
        unset($p);

        return $this->view(['plugins' => $plugins]);
    }

    /**
     * 安装插件
     */
    public function install()
    {
        if (!$this->isPost()) {
            return error('非法请求');
        }
        $identifier = $this->post['identifier'] ?? '';
        try {
            $this->service->install($identifier);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        return success('安装成功');
    }

    /**
     * 卸载插件（保留文件和数据）
     */
    public function uninstall()
    {
        if (!$this->isPost()) {
            return error('非法请求');
        }
        $identifier = $this->post['identifier'] ?? '';
        try {
            $this->service->uninstall($identifier);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        return success('卸载成功');
    }

    /**
     * 启用插件
     */
    public function enable()
    {
        if (!$this->isPost()) {
            return error('非法请求');
        }
        $identifier = $this->post['identifier'] ?? '';
        try {
            $this->service->enable($identifier);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        return success('启用成功');
    }

    /**
     * 停用插件
     */
    public function disable()
    {
        if (!$this->isPost()) {
            return error('非法请求');
        }
        $identifier = $this->post['identifier'] ?? '';
        try {
            $this->service->disable($identifier);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        return success('停用成功');
    }

    /**
     * 删除插件（物理删除）
     */
    /**
     * 升级插件
     */
    public function upgrade()
    {
        if (!$this->isPost()) return error('非法请求');
        $identifier = $this->post['identifier'] ?? '';
        try {
            $this->service->upgrade($identifier);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        return success('升级成功');
    }

    /**
     * 上传插件 ZIP 包
     */
    public function upload()
    {
        if (!$this->isPost()) {
            return error('非法请求');
        }
        $file = $this->request->file('plugin_zip');
        if (!$file) {
            return error('请选择要上传的插件包');
        }
        try {
            $identifier = $this->service->upload($file);
            // 自动扫描注册新上传的插件
            $this->service->scan();
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        return json(['code' => 1, 'msg' => '上传成功', 'data' => ['identifier' => $identifier]]);
    }
}
