<?php
namespace plugin\pay\app\controller;

use plugin\pay\app\model\Order;
use plugin\pay\app\service\PayService;
use support\Request;

/**
 * 支付回调控制器
 */
class Notify
{
    /**
     * 支付宝异步通知
     */
    public function alipay(Request $request)
    {
        $params = $request->post();
        try {
            if (!PayService::alipayVerify($params)) {
                return response('fail');
            }
            if (($params['trade_status'] ?? '') !== 'TRADE_SUCCESS') {
                return response('success');
            }
            $orderNo    = $params['out_trade_no'] ?? '';
            $outTradeNo = $params['trade_no'] ?? '';
            $order = Order::where('order_no', $orderNo)->find();
            if ($order) {
                $order->markPaid($outTradeNo, json_encode($params, JSON_UNESCAPED_UNICODE));
            }
        } catch (\Throwable $e) {
            return response('fail');
        }
        return response('success');
    }

    /**
     * 微信支付异步通知
     */
    public function wechat(Request $request)
    {
        try {
            $raw  = $request->rawBody();
            $data = json_decode($raw, true) ?? [];
            $decrypted = PayService::wechatDecryptNotify($data);

            if (($decrypted['trade_state'] ?? '') !== 'SUCCESS') {
                return json(['code' => 'SUCCESS', 'message' => 'OK']);
            }
            $orderNo    = $decrypted['out_trade_no'] ?? '';
            $outTradeNo = $decrypted['transaction_id'] ?? '';
            $order = Order::where('order_no', $orderNo)->find();
            if ($order) {
                $order->markPaid($outTradeNo, json_encode($decrypted, JSON_UNESCAPED_UNICODE));
            }
        } catch (\Throwable $e) {
            return json(['code' => 'FAIL', 'message' => $e->getMessage()]);
        }
        return json(['code' => 'SUCCESS', 'message' => 'OK']);
    }
}
