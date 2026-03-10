<?php
namespace app\admin\model;

/**
 * 系统配置模型
 */
class Config extends \think\Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'admin_configs';
    
    /**
     * 自动时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;
    
    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'create_time';
    
    /**
     * 更新时间字段
     * @var string
     */
    protected $updateTime = 'update_time';
    
    /**
     * JSON 字段
     * @var array
     */
    protected $json = ['config_options'];
    
    /**
     * 获取配置（按组分组）
     * @param string $plugin 插件标识，空=系统配置，有值=插件配置
     * @return array
     */
    public static function getConfigsByGroup(string $plugin = ''): array
    {
        $configs = self::where('plugin', $plugin)
            ->order('sort', 'asc')
            ->select()
            ->toArray();
        
        $result = [];
        foreach ($configs as $config) {
            $groupKey = $config['group_key'];
            
            if (!isset($result[$groupKey])) {
                $result[$groupKey] = [
                    'title' => $config['group_title'],
                    'config' => []
                ];
            }
            
            // 处理配置值
            $value = $config['config_value'];
            
            // 多图、多视频类型转为数组
            if (in_array($config['config_type'], ['images', 'videos'])) {
                $value = json_decode($value, true) ?: [];
            }
            // 多选框类型转为数组
            else if ($config['config_type'] === 'checkbox') {
                if (is_string($value) && !empty($value)) {
                    $value = explode(',', $value);
                } else {
                    $value = [];
                }
            }
            
            // 处理配置选项（radio, checkbox, select 需要）
            $params = [];
            if (!empty($config['config_options'])) {
                if (is_string($config['config_options'])) {
                    $params = json_decode($config['config_options'], true) ?: [];
                } else if (is_array($config['config_options'])) {
                    $params = $config['config_options'];
                }
            }
            
            $result[$groupKey]['config'][$config['config_key']] = [
                'title' => $config['config_title'],
                'type' => $config['config_type'],
                'value' => $value,
                'desc' => $config['config_desc'],
                'params' => $params
            ];
        }
        
        return $result;
    }
    
    /**
     * 保存配置
     * @param string $groupKey 配置组key
     * @param array $configData 配置数据
     * @return bool
     */
    public static function saveConfigs(string $groupKey, array $configData): bool
    {
        foreach ($configData as $configKey => $item) {
            $value = $item['value'] ?? '';
            
            // 获取配置类型
            $config = self::where([
                'group_key' => $groupKey,
                'config_key' => $configKey
            ])->find();
            
            if (!$config) {
                continue;
            }
            
            // 处理不同类型的值
            if (is_array($value)) {
                // 多选框类型转为逗号分隔的字符串
                if ($config['config_type'] === 'checkbox') {
                    $value = implode(',', $value);
                } else {
                    // 其他数组类型（images, videos）转为 JSON
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
            }
            
            $config->config_value = $value;
            $config->update_time = date('Y-m-d H:i:s');
            $config->save();
        }
        
        return true;
    }
    
    /**
     * 获取单个配置值
     * @param string $groupKey 配置组key
     * @param string $configKey 配置项key
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function getConfigValue(string $groupKey, string $configKey, $default = null)
    {
        $config = self::where([
            'group_key' => $groupKey,
            'config_key' => $configKey
        ])->find();
        
        if (!$config) {
            return $default;
        }
        
        $value = $config['config_value'];
        
        // 处理 JSON 类型
        if (in_array($config['config_type'], ['images', 'videos'])) {
            return json_decode($value, true) ?: [];
        }
        
        return $value;
    }
}
