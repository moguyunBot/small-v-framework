<?php
namespace app\admin\model;

/**
 * 插件模型
 */
class Plugin extends \think\Model
{
    protected $name = 'plugins';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $dateFormat = 'Y-m-d H:i:s';

    protected $schema = [
        'id'          => 'int',
        'identifier'  => 'string',
        'name'        => 'string',
        'version'     => 'string',
        'author'      => 'string',
        'description' => 'string',
        'icon'        => 'string',
        'status'      => 'int',
        'installed'   => 'int',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    /**
     * 获取所有已扫描到的插件（数据库中存在的）
     */
    public static function getAllPlugins(): array
    {
        return self::order('id asc')->select()->toArray();
    }

    /**
     * 根据标识查找插件
     */
    public static function findByIdentifier(string $identifier): ?self
    {
        return self::where('identifier', $identifier)->find();
    }

    /**
     * 获取所有已安装且启用的插件标识列表
     */
    public static function getActiveIdentifiers(): array
    {
        return self::where('status', 1)->where('installed', 1)->column('identifier');
    }
}
