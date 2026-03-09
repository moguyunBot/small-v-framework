<?php
namespace plugin\ipv6test\app\controller;

use support\Request;

/**
 * IPv6 连接测试插件
 */
class Index
{
    /**
     * 插件首页
     */
    public function index(Request $request)
    {
        return view('index/index');
    }
    
    /**
     * 常见问题页面
     */
    public function faq(Request $request)
    {
        return view('faq/index');
    }
    
    /**
     * 获取客户端 IP 信息（通用接口）
     */
    public function getIpInfo(Request $request)
    {
        $currentIp = $request->getRealIp();
        $isIpv4 = filter_var($currentIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        $isIpv6 = filter_var($currentIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
        
        return json([
            'code' => 0,
            'data' => [
                'current_ip' => $currentIp,
                'is_ipv4' => $isIpv4,
                'is_ipv6' => $isIpv6,
                'protocol' => $isIpv6 ? 'ipv6' : 'ipv4'
            ]
        ]);
    }
    
    /**
     * 通过外部 API 获取 IPv4 地址
     */
    public function getMyIpv4(Request $request)
    {
        try {
            // 使用只支持 IPv4 的 API
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.ipify.org?format=json');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // 强制使用 IPv4
            $response = curl_exec($ch);
            curl_close($ch);
            
            if ($response) {
                $data = json_decode($response, true);
                return json([
                    'code' => 0,
                    'data' => [
                        'ipv4' => $data['ip'] ?? null,
                        'has_ipv4' => !empty($data['ip'])
                    ]
                ]);
            }
        } catch (\Exception $e) {
            // 失败则返回当前 IP（如果是 IPv4）
            $currentIp = $request->getRealIp();
            if (filter_var($currentIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return json([
                    'code' => 0,
                    'data' => [
                        'ipv4' => $currentIp,
                        'has_ipv4' => true
                    ]
                ]);
            }
        }
        
        return json([
            'code' => 0,
            'data' => [
                'ipv4' => null,
                'has_ipv4' => false
            ]
        ]);
    }
    
    /**
     * 通过外部 API 获取 IPv6 地址
     */
    public function getMyIpv6(Request $request)
    {
        try {
            // 使用只支持 IPv6 的 API
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api64.ipify.org?format=json');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6); // 强制使用 IPv6
            $response = curl_exec($ch);
            curl_close($ch);
            
            if ($response) {
                $data = json_decode($response, true);
                return json([
                    'code' => 0,
                    'data' => [
                        'ipv6' => $data['ip'] ?? null,
                        'has_ipv6' => !empty($data['ip'])
                    ]
                ]);
            }
        } catch (\Exception $e) {
            // 失败则返回当前 IP（如果是 IPv6）
            $currentIp = $request->getRealIp();
            if (filter_var($currentIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return json([
                    'code' => 0,
                    'data' => [
                        'ipv6' => $currentIp,
                        'has_ipv6' => true
                    ]
                ]);
            }
        }
        
        return json([
            'code' => 0,
            'data' => [
                'ipv6' => null,
                'has_ipv6' => false
            ]
        ]);
    }
    
    /**
     * 获取所有可能的客户端 IP
     */
    protected function getAllClientIps(Request $request)
    {
        $ips = [];
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_CF_CONNECTING_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            $ip = $request->header(str_replace('HTTP_', '', strtolower(str_replace('_', '-', $header))));
            if (!$ip) {
                $ip = $_SERVER[$header] ?? null;
            }
            
            if ($ip) {
                // 如果是多个 IP，分割它们
                if (strpos($ip, ',') !== false) {
                    $ipList = explode(',', $ip);
                    foreach ($ipList as $singleIp) {
                        $singleIp = trim($singleIp);
                        if (filter_var($singleIp, FILTER_VALIDATE_IP)) {
                            $ips[] = $singleIp;
                        }
                    }
                } else if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $ips[] = $ip;
                }
            }
        }
        
        // 添加 getRealIp
        $realIp = $request->getRealIp();
        if ($realIp && filter_var($realIp, FILTER_VALIDATE_IP)) {
            $ips[] = $realIp;
        }
        
        return array_unique($ips);
    }
    
    /**
     * DNS 解析测试
     */
    public function testDns(Request $request)
    {
        $domain = $request->post('domain', 'ipv6.google.com');
        
        $ipv4Records = @dns_get_record($domain, DNS_A);
        $hasIpv4Dns = !empty($ipv4Records);
        
        $ipv6Records = @dns_get_record($domain, DNS_AAAA);
        $hasIpv6Dns = !empty($ipv6Records);
        
        // 如果 dns_get_record 失败，尝试使用其他域名
        if (!$hasIpv6Dns) {
            $testDomains = ['ipv6.google.com', 'www.google.com', 'ipv6.test-ipv6.com'];
            foreach ($testDomains as $testDomain) {
                $records = @dns_get_record($testDomain, DNS_AAAA);
                if (!empty($records)) {
                    $hasIpv6Dns = true;
                    $ipv6Records = $records;
                    break;
                }
            }
        }
        
        return json([
            'code' => 0,
            'data' => [
                'domain' => $domain,
                'ipv4_records' => $ipv4Records ?: [],
                'ipv6_records' => $ipv6Records ?: [],
                'has_ipv4_dns' => $hasIpv4Dns,
                'has_ipv6_dns' => $hasIpv6Dns
            ]
        ]);
    }
    
    /**
     * 连接速度测试
     */
    public function testSpeed(Request $request)
    {
        $testUrl = $request->post('url', 'https://www.google.com/');
        
        $ipv4Speed = $this->testConnectionSpeed($testUrl, 4);
        $ipv6Speed = $this->testConnectionSpeed($testUrl, 6);
        
        return json([
            'code' => 0,
            'data' => [
                'ipv4_speed' => $ipv4Speed,
                'ipv6_speed' => $ipv6Speed,
                'faster' => $this->compareSpeeds($ipv4Speed, $ipv6Speed)
            ]
        ]);
    }
    
    /**
     * 获取客户端 IPv4 地址
     */
    protected function getClientIpv4(Request $request)
    {
        // 尝试从多个来源获取 IPv4 地址
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_CF_CONNECTING_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            $ip = $request->header(str_replace('HTTP_', '', strtolower(str_replace('_', '-', $header))));
            if (!$ip) {
                $ip = $_SERVER[$header] ?? null;
            }
            
            if ($ip) {
                // 如果是多个 IP，取第一个
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // 检查是否为 IPv4
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    return $ip;
                }
            }
        }
        
        // 最后尝试 getRealIp
        $ip = $request->getRealIp();
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ip;
        }
        
        return null;
    }
    
    /**
     * 获取客户端 IPv6 地址
     */
    protected function getClientIpv6(Request $request)
    {
        // 尝试从多个来源获取 IPv6 地址
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_CF_CONNECTING_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            $ip = $request->header(str_replace('HTTP_', '', strtolower(str_replace('_', '-', $header))));
            if (!$ip) {
                $ip = $_SERVER[$header] ?? null;
            }
            
            if ($ip) {
                // 如果是多个 IP，取第一个
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // 检查是否为 IPv6
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    return $ip;
                }
            }
        }
        
        // 最后尝试 getRealIp
        $ip = $request->getRealIp();
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $ip;
        }
        
        return null;
    }
    
    /**
     * 测试连接速度
     */
    protected function testConnectionSpeed($url, $ipVersion = 4)
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if ($ipVersion == 4) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        } else {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6);
        }
        
        $startTime = microtime(true);
        $result = curl_exec($ch);
        $endTime = microtime(true);
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        $responseTime = round(($endTime - $startTime) * 1000, 2);
        
        return [
            'success' => $result !== false && $httpCode == 200,
            'response_time' => $responseTime,
            'http_code' => $httpCode,
            'error' => $error
        ];
    }
    
    /**
     * 比较速度
     */
    protected function compareSpeeds($ipv4Speed, $ipv6Speed)
    {
        if (!$ipv4Speed['success'] && !$ipv6Speed['success']) {
            return 'both_failed';
        }
        
        if (!$ipv4Speed['success']) {
            return 'ipv6';
        }
        
        if (!$ipv6Speed['success']) {
            return 'ipv4';
        }
        
        if ($ipv4Speed['response_time'] < $ipv6Speed['response_time']) {
            return 'ipv4';
        } else if ($ipv6Speed['response_time'] < $ipv4Speed['response_time']) {
            return 'ipv6';
        } else {
            return 'equal';
        }
    }
}
