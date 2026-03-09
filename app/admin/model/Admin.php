<?php

namespace app\admin\model;

/**
 * 管理员模型
 */
class Admin extends \think\Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'admin_users';
    
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
     * 获取角色ID列表
     * @param mixed $v
     * @param array $data
     * @return array
     */
    public function getRoleIdsAttr($v, $data)
    {
        return AdminRole::where(['admin_id' => $data['id']])->column('role_id');
    }
}
