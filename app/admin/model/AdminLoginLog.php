<?php
namespace app\admin\model;

/**
 * 管理员登录日志
 */
class AdminLoginLog extends \think\Model
{
    protected $name = 'admin_login_log';
    protected $autoWriteTimestamp = false;

    protected $schema = [
        'id'          => 'int',
        'admin_id'    => 'int',
        'username'    => 'string',
        'ip'          => 'string',
        'ua'          => 'string',
        'status'      => 'int',
        'remark'      => 'string',
        'create_time' => 'int',
    ];

    /**
     * 写入登录日志
     */
    public static function record(string $username, int $adminId, int $status, string $remark = ''): void
    {
        $request = request();
        self::create([
            'admin_id'    => $adminId,
            'username'    => $username,
            'ip'          => $request->getRealIp(),
            'ua'          => substr($request->header('user-agent', ''), 0, 500),
            'status'      => $status,
            'remark'      => $remark,
            'create_time' => time(),
        ]);
    }
}
