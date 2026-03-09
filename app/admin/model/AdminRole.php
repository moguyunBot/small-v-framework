<?php

namespace app\admin\model;

/**
 * 管理员角色关联模型
 */
class AdminRole extends \think\Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'admin_user_roles';
    
    /**
     * 自动时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = false;
}
