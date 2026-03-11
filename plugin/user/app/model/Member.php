<?php
namespace plugin\user\app\model;

use think\Model;

class Member extends Model
{
    protected $table = 'user_members';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    /**
     * 密码隐藏
     */
    protected $hidden = ['password'];

    /**
     * 根据邮箱或手机号查找用户
     */
    public static function findByAccount(string $account): ?static
    {
        return static::where('email', $account)
            ->orerWhere('mobile', $account)
            ->find();
    }

    /**
     * 验证密码
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    /**
     * 设置密码（自动 hash）
     */
    public function setPasswordAttr(string $value): string
    {
        return $value ? password_hash($value, PASSWORD_DEFAULT) : $this->getOrigin('password');
    }

    /**
     * 调整余额
     * @param float $amount 正数增加，负数减少
     * @param string $remark 备注
     */
    public function changeBalance(float $amount, string $remark = ''): bool
    {
        if ($amount > 0) {
            static::where('id', $this->id)->inc('balance', $amount);
        } elseif ($amount < 0) {
            if ($this->balance + $amount < 0) {
                throw new \Exception('余额不足');
            }
            static::where('id', $this->id)->dec('balance', abs($amount));
        }
        \Webman\Event\Event::emit('user.balance.changed', [
            'user_id' => $this->id,
            'amount'  => $amount,
            'remark'  => $remark,
        ]);
        return true;
    }
}
