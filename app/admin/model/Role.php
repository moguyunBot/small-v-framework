<?php

namespace app\admin\model;

/**
 * 角色模型
 */
class Role extends \think\Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'admin_roles';
    
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
}
