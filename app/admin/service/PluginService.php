<?php
namespace app\admin\service;

use app\admin\model\Plugin as PluginModel;
use app\admin\model\Rule;
use app\admin\model\Config as ConfigModel;
use app\admin\model\ConfigGroup as ConfigGroupModel;

/**
 * 插件服务类
 * 负责插件的安装、卸载、启用、停用、删除、扫描、上传
 */
class PluginService
{
    /**
     * 扫描 plugin/ 目录，将未注册的插件写入 plugins 表（状态=未安装）
     */
    public function scan(): array
    {
        $pluginDir = base_path('plugin');
        if (!is_dir($pluginDir)) {
            return [];
        }

        $dirs = array_filter(glob($pluginDir . '/*'), 'is_dir');
        $newPlugins = [];

        foreach ($dirs as $dir) {
            $identifier = basename($dir);
            $appConfig  = $dir . '/config/app.php';

            if (!file_exists($appConfig)) {
                continue;
            }

            $config = require $appConfig;

            // 已在数据库中，跳过
            if (PluginModel::findByIdentifier($identifier)) {
                continue;
            }

            // 写入 plugins 表（未安装状态）
            $plugin = PluginModel::create([
                'identifier'  => $identifier,
                'name'        => $config['name']        ?? $identifier,
                'version'     => $config['version']     ?? '1.0.0',
                'author'      => $config['author']      ?? '',
                'description' => $config['description'] ?? '',
                'icon'        => $config['icon']        ?? 'mdi mdi-puzzle',
                'status'      => 0,
                'installed'   => 0,
            ]);

            $newPlugins[] = $plugin;
        }

        return $newPlugins;
    }

    /**
     * 安装插件
     * 首次安装：从 config/menu.php 写入 admin_rules，从 config/app.php[settings] 写入配置
     * 重装：直接恢复 status=1
     */
    public function install(string $identifier): bool
    {
        $plugin = PluginModel::findByIdentifier($identifier);
        if (!$plugin) {
            throw new \Exception('插件不存在');
        }

        $hasRules = Rule::where('plugin', $identifier)->count();

        if (!$hasRules) {
            // 首次安装：执行 install.sql
            $this->runInstallSql($identifier);
            // 导入菜单规则和配置
            $this->importMenuRules($identifier);
            $this->importConfigs($identifier);
        } else {
            // 重装：恢复状态
            Rule::where('plugin', $identifier)->update(['status' => 1]);
            ConfigGroupModel::where('plugin', $identifier)->update(['status' => 1]);
            ConfigModel::where('plugin', $identifier)->update(['status' => 1]);
            // 补充写入缺失的配置项（已有的不覆盖）
            $this->importConfigs($identifier);
        }

        $plugin->save(['installed' => 1, 'status' => 1]);
        return true;
    }

    /**
     * 停用插件（保留数据，隐藏菜单）
     */
    public function disable(string $identifier): bool
    {
        $plugin = PluginModel::findByIdentifier($identifier);
        if (!$plugin) {
            throw new \Exception('插件不存在');
        }
        $plugin->save(['status' => 0]);
        Rule::where('plugin', $identifier)->update(['status' => 0]);
        ConfigGroupModel::where('plugin', $identifier)->update(['status' => 0]);
        ConfigModel::where('plugin', $identifier)->update(['status' => 0]);
        return true;
    }

    /**
     * 启用插件
     */
    public function enable(string $identifier): bool
    {
        $plugin = PluginModel::findByIdentifier($identifier);
        if (!$plugin) {
            throw new \Exception('插件不存在');
        }
        if (!$plugin['installed']) {
            throw new \Exception('请先安装插件');
        }
        $plugin->save(['status' => 1]);
        Rule::where('plugin', $identifier)->update(['status' => 1]);
        ConfigGroupModel::where('plugin', $identifier)->update(['status' => 1]);
        ConfigModel::where('plugin', $identifier)->update(['status' => 1]);
        return true;
    }

    /**
     * 卸载插件（保留文件和数据库记录，只改状态）
     */
    public function uninstall(string $identifier): bool
    {
        $plugin = PluginModel::findByIdentifier($identifier);
        if (!$plugin) {
            throw new \Exception('插件不存在');
        }
        $plugin->save(['installed' => 0, 'status' => 0]);
        // 卸载时直接删除菜单规则，避免重复安装产生重复数据
        Rule::where('plugin', $identifier)->delete();
        ConfigGroupModel::where('plugin', $identifier)->update(['status' => 0]);
        ConfigModel::where('plugin', $identifier)->update(['status' => 0]);
        return true;
    }

    /**
     * 删除插件（物理删除文件 + 清空数据库所有记录）
     * 必须先卸载才能删除
     */
    public function delete(string $identifier): bool
    {
        $plugin = PluginModel::findByIdentifier($identifier);
        if (!$plugin) {
            throw new \Exception('插件不存在');
        }
        if ($plugin['installed']) {
            throw new \Exception('请先卸载插件再删除');
        }

        // 清空数据库记录
        Rule::where('plugin', $identifier)->delete();
        ConfigModel::where('plugin', $identifier)->delete();
        ConfigGroupModel::where('plugin', $identifier)->delete();
        $plugin->delete();

        // 删除插件目录
        $dir = base_path('plugin/' . $identifier);
        if (is_dir($dir)) {
            $this->deleteDirectory($dir);
        }

        return true;
    }

    /**
     * 上传并解压 ZIP 插件包
     */
    public function upload(\support\UploadFile $file): string
    {
        if ($file->getUploadExtension() !== 'zip') {
            throw new \Exception('只支持上传 ZIP 格式的插件包');
        }

        $tmpPath = $file->getRealPath();
        $zip = new \ZipArchive();

        if ($zip->open($tmpPath) !== true) {
            throw new \Exception('ZIP 文件无法打开，请检查文件是否完整');
        }

        // 读取 config/app.php 确认 identifier
        $appConfigContent = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#config/app\.php$#', $name)) {
                $appConfigContent = $zip->getFromIndex($i);
                break;
            }
        }

        if (!$appConfigContent) {
            $zip->close();
            throw new \Exception('无效的插件包：缺少 config/app.php');
        }

        // 安全解析 identifier
        if (!preg_match("/'identifier'\s*=>\s*'([a-zA-Z0-9_]+)'/", $appConfigContent, $matches)) {
            $zip->close();
            throw new \Exception('无效的插件包：config/app.php 中缺少 identifier 字段');
        }

        $identifier = $matches[1];
        $targetDir  = base_path('plugin/' . $identifier);

        // 解压
        $zip->extractTo($targetDir);
        $zip->close();

        return $identifier;
    }

    /**
     * 执行插件的 install.sql（仅首次安装）
     */
    protected function runInstallSql(string $identifier): void
    {
        $sqlFile = base_path("plugin/{$identifier}/install.sql");
        if (!file_exists($sqlFile)) {
            return;
        }

        $sql = file_get_contents($sqlFile);
        if (empty(trim($sql))) {
            return;
        }

        // 按分号拆分多条语句逐条执行
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($s) => $s !== ''
        );

        foreach ($statements as $statement) {
            \think\facade\Db::execute($statement);
        }
    }

    /**
     * 从 config/menu.php 导入菜单规则到 admin_rules
     */
    protected function importMenuRules(string $identifier): void
    {
        $menuFile = base_path("plugin/{$identifier}/config/menu.php");
        if (!file_exists($menuFile)) {
            return;
        }

        $config = require $menuFile;
        $menus  = $config['menus'] ?? [];
        $perms  = $config['permissions'] ?? [];

        // 递归写入菜单
        foreach ($menus as $menu) {
            $this->insertMenuRule($menu, 0, $identifier, 1);
        }

        // 写入额外权限节点（非菜单）
        foreach ($perms as $perm) {
            Rule::create([
                'title'   => $perm['title'],
                'href'    => $perm['href'] ?? '',
                'icon'    => '',
                'pid'     => 0,
                'sort'    => $perm['sort'] ?? 99,
                'is_menu' => 0,
                'status'  => 1,
                'type'    => 'plugin',
                'plugin'  => $identifier,
            ]);
        }
    }

    /**
     * 递归插入菜单规则
     */
    protected function insertMenuRule(array $menu, int $pid, string $identifier, int $isMenu): void
    {
        // 优先使用菜单配置里的 is_menu 字段
        $isMenuVal = isset($menu['is_menu']) ? (int)$menu['is_menu'] : $isMenu;

        $rule = Rule::create([
            'title'   => $menu['title'],
            'href'    => $menu['href']  ?? '',
            'icon'    => $menu['icon']  ?? '',
            'pid'     => $pid,
            'sort'    => $menu['sort']  ?? 0,
            'is_menu' => $isMenuVal,
            'status'  => 1,
            'type'    => 'plugin',
            'plugin'  => $identifier,
        ]);

        foreach ($menu['children'] ?? [] as $child) {
            $this->insertMenuRule($child, $rule->id, $identifier, 1);
        }
    }

    /**
     * 从 config/app.php[settings] 导入配置项到数据库
     */
    protected function importConfigs(string $identifier): void
    {
        $appFile = base_path("plugin/{$identifier}/config/app.php");
        if (!file_exists($appFile)) {
            return;
        }

        $config   = require $appFile;
        $settings = $config['settings'] ?? [];

        if (empty($settings)) {
            return;
        }

        // 写入配置项（支持两种格式：分组格式 和 扁平键值格式）
        // 分组格式：[['group_key'=>'x','group_title'=>'x','configs'=>[...]]]
        // 扁平格式：['key' => ['title'=>'x','type'=>'x',...]]
        $isGroupFormat = isset($settings[0]) && isset($settings[0]['configs']);

        if ($isGroupFormat) {
            foreach ($settings as $groupDef) {
                $gKey   = $groupDef['group_key']   ?? $identifier;
                $gTitle = $groupDef['group_title']  ?? $identifier . ' 配置';

                // 创建/更新分组
                $group = ConfigGroupModel::where('group_key', $gKey)->where('plugin', $identifier)->find();
                if (!$group) {
                    ConfigGroupModel::create(['group_key' => $gKey, 'group_title' => $gTitle, 'sort' => 0, 'plugin' => $identifier]);
                }

                foreach ($groupDef['configs'] ?? [] as $cfg) {
                    $cKey = $cfg['config_key'] ?? '';
                    if (!$cKey) continue;
                    $exists = ConfigModel::where('config_key', $cKey)->where('plugin', $identifier)->find();
                    if (!$exists) {
                        $options = $cfg['config_options'] ?? '';
                        if (is_array($options)) {
                            $options = json_encode($options, JSON_UNESCAPED_UNICODE);
                        }
                        ConfigModel::create([
                            'group_key'      => $gKey,
                            'group_title'    => $gTitle,
                            'config_key'     => $cKey,
                            'config_title'   => $cfg['config_title']   ?? $cKey,
                            'config_type'    => $cfg['config_type']    ?? 'text',
                            'config_value'   => (string)($cfg['config_value'] ?? ''),
                            'config_desc'    => $cfg['config_desc']    ?? '',
                            'config_options' => $options,
                            'sort'           => $cfg['sort']           ?? 0,
                            'plugin'         => $identifier,
                        ]);
                    }
                }
            }
        } else {
            // 扁平键值格式（旧格式兼容）
            $groupKey = $identifier;
            $group = ConfigGroupModel::where('group_key', $groupKey)->find();
            if (!$group) {
                ConfigGroupModel::create(['group_key' => $groupKey, 'group_title' => ($config['name'] ?? $identifier) . ' 配置', 'sort' => 0, 'plugin' => $identifier]);
            }
            foreach ($settings as $key => $setting) {
                $exists = ConfigModel::where('config_key', $key)->where('group_key', $groupKey)->find();
                if (!$exists) {
                    ConfigModel::create([
                        'group_key'      => $groupKey,
                        'group_title'    => ($config['name'] ?? $identifier) . ' 配置',
                        'config_key'     => $key,
                        'config_title'   => $setting['title']   ?? $key,
                        'config_type'    => $setting['type']    ?? 'text',
                        'config_value'   => (string)($setting['default'] ?? ''),
                        'config_desc'    => $setting['desc']    ?? '',
                        'config_options' => isset($setting['options']) ? json_encode($setting['options'], JSON_UNESCAPED_UNICODE) : '',
                        'sort'           => $setting['sort']    ?? 0,
                        'plugin'         => $identifier,
                    ]);
                }
            }
        }
    }

    /**
     * 递归删除目录
     */
    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
