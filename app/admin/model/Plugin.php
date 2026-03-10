<?php
namespace app\admin\model;

use think\Model;

/**
 * 插件模型
 */
class Plugin extends Model
{
    /**
     * 表名
     */
    protected $name = 'plugins';
    
    /**
     * 自动时间戳
     */
    protected $autoWriteTimestamp = true;
    
    /**
     * 创建时间字段
     */
    protected $createTime = 'create_time';
    
    /**
     * 更新时间字段
     */
    protected $updateTime = 'update_time';
    
    /**
     * 获取插件配置（从文件）
     */
    public static function getPluginConfig(string $identifier): ?array
    {
        $configFile = plugin_path($identifier) . '/config/plugin.php';
        if (!file_exists($configFile)) {
            return null;
        }
        return include $configFile;
    }
    
    /**
     * 获取插件菜单配置（从文件）
     */
    public static function getPluginMenu(string $identifier): ?array
    {
        $menuFile = plugin_path($identifier) . '/config/menu.php';
        if (!file_exists($menuFile)) {
            return null;
        }
        return include $menuFile;
    }
    
    /**
     * 安装插件
     */
    public function install(): bool
    {
        if ($this->is_installed) {
            return true;
        }
        
        $identifier = $this->identifier;
        
        // 1. 执行数据库安装脚本
        $sqlFile = plugin_path($identifier) . '/database/install.sql';
        if (file_exists($sqlFile)) {
            $this->executeSql($sqlFile);
        }
        
        // 2. 注册菜单
        $this->registerMenus();
        
        // 3. 更新状态
        $this->is_installed = 1;
        $this->status = 1;
        $this->install_time = date('Y-m-d H:i:s');
        $this->save();
        
        // 4. 更新插件配置文件
        $this->updatePluginConfig(['enable' => true]);
        
        return true;
    }
    
    /**
     * 卸载插件（保留数据）
     */
    public function uninstall(): bool
    {
        if (!$this->is_installed) {
            return true;
        }
        
        $identifier = $this->identifier;
        
        // 1. 删除菜单（只删除 is_system=0 的菜单）
        Rule::where('plugin', $identifier)->where('is_system', 0)->delete();
        
        // 2. 更新状态（不执行 uninstall.sql，保留数据）
        $this->is_installed = 0;
        $this->status = 0;
        $this->save();
        
        // 3. 更新插件配置文件
        $this->updatePluginConfig(['enable' => false]);
        
        return true;
    }
    
    /**
     * 切换启用状态
     */
    public function toggle(): bool
    {
        $this->status = !$this->status;
        $this->save();
        
        // 同步更新菜单状态
        Rule::where('plugin', $this->identifier)
            ->where('is_system', 0)
            ->update(['status' => $this->status]);
        
        // 更新插件配置文件
        $this->updatePluginConfig(['enable' => (bool)$this->status]);
        
        return true;
    }
    
    /**
     * 注册插件菜单
     */
    protected function registerMenus(): void
    {
        $menuConfig = self::getPluginMenu($this->identifier);
        if (!$menuConfig || empty($menuConfig['menus'])) {
            return;
        }
        
        // 获取插件管理菜单ID作为父级
        $parentId = Rule::where('href', '/admin/plugin/index')->value('id') ?: 0;
        
        foreach ($menuConfig['menus'] as $menu) {
            $this->createMenuRecursive($menu, $parentId);
        }
    }
    
    /**
     * 递归创建菜单
     */
    protected function createMenuRecursive(array $menu, int $pid = 0): void
    {
        $rule = Rule::create([
            'pid'       => $pid,
            'title'     => $menu['title'],
            'href'      => $menu['href'],
            'icon'      => $menu['icon'] ?? '',
            'sort'      => $menu['sort'] ?? 0,
            'is_menu'   => 1,
            'status'    => 1,
            'type'      => 'plugin',
            'plugin'    => $this->identifier,
            'is_system' => 0,
        ]);
        
        // 递归创建子菜单
        if (!empty($menu['children'])) {
            foreach ($menu['children'] as $child) {
                $this->createMenuRecursive($child, $rule->id);
            }
        }
    }
    
    /**
     * 执行 SQL 文件
     */
    protected function executeSql(string $sqlFile): void
    {
        $sql = file_get_contents($sqlFile);
        
        // 分割多条 SQL
        $queries = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($queries as $query) {
            if (!empty($query)) {
                try {
                    \think\facade\Db::execute($query);
                } catch (\Exception $e) {
                    // 忽略已存在的错误
                    if (!str_contains($e->getMessage(), 'Duplicate') && 
                        !str_contains($e->getMessage(), 'already exists')) {
                        throw $e;
                    }
                }
            }
        }
    }
    
    /**
     * 更新插件配置文件
     */
    protected function updatePluginConfig(array $config): void
    {
        $configFile = plugin_path($this->identifier) . '/config/plugin.php';
        if (!file_exists($configFile)) {
            return;
        }
        
        $currentConfig = include $configFile;
        $newConfig = array_merge($currentConfig, $config);
        
        $content = "<?php\nreturn " . var_export($newConfig, true) . ";\n";
        file_put_contents($configFile, $content);
    }
    
    /**
     * 从目录扫描插件
     */
    public static function scanPlugins(): array
    {
        $pluginDir = plugin_path();
        if (!is_dir($pluginDir)) {
            return [];
        }
        
        $plugins = [];
        $dirs = glob($pluginDir . '/*', GLOB_ONLYDIR);
        
        foreach ($dirs as $dir) {
            $identifier = basename($dir);
            $config = self::getPluginConfig($identifier);
            
            if ($config) {
                $plugins[] = [
                    'identifier'  => $identifier,
                    'name'        => $config['name'] ?? $identifier,
                    'version'     => $config['version'] ?? '1.0.0',
                    'author'      => $config['author'] ?? '',
                    'description' => $config['description'] ?? '',
                    'icon'        => $config['icon'] ?? 'mdi mdi-puzzle',
                    'has_menu'    => file_exists($dir . '/config/menu.php'),
                ];
            }
        }
        
        return $plugins;
    }
}
