<?php
namespace plugin\pay\app\service;

use plugin\pay\app\model\Order;

/**
 * 统一支付服务
 * 使用方式：
 *   $url = PayService::create('alipay', $orderNo, 100.00, '商品标题', $userId, $returnUrl);
 *   $url = PayService::create('wechat', $orderNo, 100.00, '商品标题', $userId);
 */
class PayService
{
    /**
     * 创建支付订单并返回支付 URL/参数
     *
     * @param string $payType   支付方式：alipay | wechat
     * @param string $orderNo   业务订单号（调用方自行生成或传 Order::makeOrderNo()）
     * @param float  $amount    金额（元）
     * @param string $subject   订单标题
     * @param int    $userId    用户ID
     * @param string $returnUrl 同步跳转地址（支付宝使用）
     * @return mixed            支付宝返回 HTML 表单字符串，微信返回 ['code_url'=>...] 或 ['mweb_url'=>...]
     */
    public static function create(
        string $payType,
        string $orderNo,
        float  $amount,
        string $subject,
        int    $userId = 0,
        string $returnUrl = ''
    ): mixed {
        // 写入订单表（幂等：已存在则跳过）
        if (!Order::where('order_no', $orderNo)->find()) {
            Order::create([
                'order_no'  => $orderNo,
                'user_id'   => $userId,
                'amount'    => $amount,
                'subject'   => $subject,
                'pay_type'  => $payType,
                'status'    => Order::STATUS_PENDING,
            ]);
        }

        return match ($payType) {
            'alipay' => self::alipay($orderNo, $amount, $subject, $returnUrl),
            'wechat' => self::wechat($orderNo, $amount, $subject),
            default  => throw new \Exception('不支持的支付方式: ' . $payType),
        };
    }

    // -------------------------
    // 支付宝
    // -------------------------
    protected static function alipay(string $orderNo, float $amount, string $subject, string $returnUrl): string
    {
        $cfg = [
            'app_id'      => get_config('pay_alipay.app_id'),
            'private_key' => get_config('pay_alipay.private_key'),
            'public_key'  => get_config('pay_alipay.public_key'),
            'notify_url'  => get_config('pay_alipay.notify_url'),
            'return_url'  => $returnUrl ?: get_config('pay_alipay.return_url'),
            'sandbox'     => (bool)get_config('pay_alipay.sandbox', 0),
        ];

        $gateway = $cfg['sandbox']
            ? 'https://openapi-sandbox.dl.alipaydev.com/gateway.do'
            : 'https://openapi.alipay.com/gateway.do';

        $params = [
            'app_id'    => $cfg['app_id'],
            'method'    => 'alipay.trade.page.pay',
            'charset'   => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version'   => '1.0',
            'notify_url'=> $cfg['notify_url'],
            'return_url'=> $cfg['return_url'],
            'biz_content' => json_encode([
                'out_trade_no' => $orderNo,
                'total_amount' => number_format($amount, 2, '.', ''),
                'subject'      => $subject,
                'product_code' => 'FAST_INSTANT_TRADE_PAY',
            ], JSON_UNESCAPED_UNICODE),
        ];

        // RSA2 签名
        $params['sign'] = self::alipaySign($params, $cfg['private_key']);

        // 生成自动提交表单
        $html = '<form id="alipayForm" action="' . $gateway . '" method="post">';
        foreach ($params as $k => $v) {
            $html .= '<input type="hidden" name="' . htmlspecialchars($k) . '" value="' . htmlspecialchars($v) . '">';
        }
        $html .= '</form><script>document.getElementById("alipayForm").submit();</script>';
        return $html;
    }

    protected static function alipaySign(array $params, string $privateKey): string
    {
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) {
            if ($v === '' || $v === null) continue;
            $str .= ($str ? '&' : '') . "{$k}={$v}";
        }
        $key = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($privateKey, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
        openssl_sign($str, $sign, $key, OPENSSL_ALGO_SHA256);
        return base64_encode($sign);
    }

    /**
     * 支付宝回调验签
     */
    public static function alipayVerify(array $params): bool
    {
        $sign      = base64_decode($params['sign'] ?? '');
        $signType  = $params['sign_type'] ?? 'RSA2';
        $publicKey = get_config('pay_alipay.public_key');
        unset($params['sign'], $params['sign_type']);
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) {
            if ($v === '' || $v === null) continue;
            $str .= ($str ? '&' : '') . "{$k}={$v}";
        }
        $key = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($publicKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        return openssl_verify($str, $sign, $key, OPENSSL_ALGO_SHA256) === 1;
    }

    // -------------------------
    // 微信支付 v3 Native
    // -------------------------
    protected static function wechat(string $orderNo, float $amount, string $subject): array
    {
        $cfg = [
            'app_id'     => get_config('pay_wechat.app_id'),
            'mch_id'     => get_config('pay_wechat.mch_id'),
            'api_key'    => get_config('pay_wechat.api_key'),
            'notify_url' => get_config('pay_wechat.notify_url'),
            'cert_path'  => get_config('pay_wechat.cert_path'),
            'key_path'   => get_config('pay_wechat.key_path'),
        ];

        $body = [
            'appid'        => $cfg['app_id'],
            'mchid'        => $cfg['mch_id'],
            'description'  => $subject,
            'out_trade_no' => $orderNo,
            'notify_url'   => $cfg['notify_url'],
            'amount'       => ['total' => (int)round($amount * 100), 'currency' => 'CNY'],
        ];

        $url    = 'https://api.mch.weixin.qq.com/v3/pay/transactions/native';
        $result = self::wechatRequest('POST', $url, $body, $cfg);
        return $result; // ['code_url' => 'weixin://...'] 用于生成二维码
    }

    /**
     * 微信支付 v3 签名请求
     */
    protected static function wechatRequest(string $method, string $url, array $body, array $cfg): array
    {
        $timestamp  = time();
        $nonce      = bin2hex(random_bytes(16));
        $bodyStr    = $body ? json_encode($body, JSON_UNESCAPED_UNICODE) : '';
        $urlPath    = parse_url($url, PHP_URL_PATH);
        $query      = parse_url($url, PHP_URL_QUERY);
        if ($query) $urlPath .= '?' . $query;

        $message    = "{$method}\n{$urlPath}\n{$timestamp}\n{$nonce}\n{$bodyStr}\n";
        $privateKey = file_get_contents($cfg['key_path']);
        openssl_sign($message, $sign, $privateKey, OPENSSL_ALGO_SHA256);
        $sign = base64_encode($sign);

        $serialNo   = self::wechatGetSerialNo($cfg['cert_path']);
        $authHeader = "WECHATPAY2-SHA256-RSA2048 mchid=\"{$cfg['mch_id']}\",nonce_str=\"{$nonce}\",timestamp=\"{$timestamp}\",serial_no=\"{$serialNo}\",signature=\"{$sign}\"";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $bodyStr,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: ' . $authHeader,
            ],
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return json_decode($resp, true) ?? [];
    }

    protected static function wechatGetSerialNo(string $certPath): string
    {
        $cert = file_get_contents($certPath);
        $info = openssl_x509_parse($cert);
        return $info['serialNumberHex'] ?? '';
    }

    /**
     * 微信支付 v3 回调验签并解密
     */
    public static function wechatDecryptNotify(array $data): array
    {
        $apiKey        = get_config('pay_wechat.api_key');
        $resource      = $data['resource'] ?? [];
        $ciphertext    = base64_decode($resource['ciphertext']   ?? '');
        $associatedData= $resource['associated_data'] ?? '';
        $nonce         = $resource['nonce'] ?? '';

        // AES-256-GCM 解密
        $tag       = substr($ciphertext, -16);
        $ciphertext= substr($ciphertext, 0, -16);
        $decrypted = openssl_decrypt($ciphertext, 'aes-256-gcm', $apiKey, OPENSSL_RAW_DATA, $nonce, $tag, $associatedData);
        return json_decode($decrypted, true) ?? [];
    }
}
