<?php
namespace plugin\pay\app\model;

use think\Model;

class Order extends Model
{
    protected $table = 'pay_orders';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 状态常量
    const STATUS_PENDING = 0;
    const STATUS_PAID    = 1;
    const STATUS_REFUND  = 2;

    /**
     * 生成唯一订单号
     */
    public static function makeOrderNo(): string
    {
        return date('YmdHis') . substr(str_pad(mt_rand(), 6, '0', STR_PAD_LEFT), 0, 6);
    }

    /**
     * 标记为已支付
     */
    public function markPaid(string $outTradeNo, string $extra = ''): void
    {
        if ($this->status === self::STATUS_PAID) return;
        $this->save([
            'status'       => self::STATUS_PAID,
            'out_trade_no' => $outTradeNo,
            'extra'        => $extra,
            'paid_at'      => time(),
        ]);
        \Webman\Event\Event::emit('payment.paid', [
            'order_no'     => $this->order_no,
            'out_trade_no' => $outTradeNo,
            'user_id'      => $this->user_id,
            'amount'       => $this->amount,
            'pay_type'     => $this->pay_type,
            'subject'      => $this->subject,
        ]);
    }
}
