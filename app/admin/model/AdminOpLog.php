<?php
namespace app\admin\model;

/**
 * 管理员操作日志
 */
class AdminOpLog extends \think\Model
{
    protected $name = 'admin_op_log';
    protected $autoWriteTimestamp = false;

    protected $schema = [
        'id'          => 'int',
        'admin_id'    => 'int',
        'username'    => 'string',
        'path'        => 'string',
        'method'      => 'string',
        'params'      => 'string',
        'ip'          => 'string',
        'create_time' => 'int',
    ];

    /**
     * 写入操作日志
     */
    public static function record(): void
    {
        $request = request();
        $admin   = admin();
        if (!$admin) return;

        // 过滤不需要记录的路径
        $path = $request->path();
        $skip = ['/admin/Index/login', '/admin/Index/captcha', '/admin/Index/getSystemData', '/admin/Config/uploadImage'];
        if (in_array($path, $skip)) return;

        // 过滤敏感字段
        $params = $request->post();
        foreach (['password', 'old_password', 'new_password', 'confirm_password'] as $key) {
            if (isset($params[$key])) $params[$key] = '******';
        }

        self::create([
            'admin_id'    => $admin['id'],
            'username'    => $admin['username'],
            'path'        => $path,
            'method'      => $request->method(),
            'params'      => json_encode($params, JSON_UNESCAPED_UNICODE),
            'ip'          => $request->getRealIp(),
            'create_time' => time(),
        ]);
    }
}
