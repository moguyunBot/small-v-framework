<?php
namespace plugin\nettools\app\controller;

use support\Request;

class Index
{
    public function index(Request $request)
    {
        return view('index/index');
    }
    
    // Ping 测试
    public function ping(Request $request)
    {
        $host = $request->post('host', '');
        
        if (empty($host)) {
            return json(['code' => -1, 'msg' => '请输入主机地址']);
        }
        
        // 清理输入
        $host = escapeshellarg($host);
        
        // 执行 ping 命令
        $output = [];
        exec("ping -c 4 $host 2>&1", $output, $return_var);
        
        return json([
            'code' => 0,
            'data' => [
                'host' => trim($host, "'"),
                'result' => implode("\n", $output),
                'success' => $return_var === 0
            ]
        ]);
    }
    
    // DNS 查询
    public function dns(Request $request)
    {
        $domain = $request->post('domain', '');
        $type = $request->post('type', 'A');
        
        if (empty($domain)) {
            return json(['code' => -1, 'msg' => '请输入域名']);
        }
        
        $typeMap = [
            'A' => DNS_A,
            'AAAA' => DNS_AAAA,
            'MX' => DNS_MX,
            'TXT' => DNS_TXT,
            'CNAME' => DNS_CNAME,
            'NS' => DNS_NS
        ];
        
        $dnsType = $typeMap[$type] ?? DNS_A;
        $records = @dns_get_record($domain, $dnsType);
        
        return json([
            'code' => 0,
            'data' => [
                'domain' => $domain,
                'type' => $type,
                'records' => $records ?: []
            ]
        ]);
    }
    
    // Whois 查询
    public function whois(Request $request)
    {
        $domain = $request->post('domain', '');
        
        if (empty($domain)) {
            return json(['code' => -1, 'msg' => '请输入域名']);
        }
        
        $domain = escapeshellarg($domain);
        $output = [];
        exec("whois $domain 2>&1", $output, $return_var);
        
        return json([
            'code' => 0,
            'data' => [
                'domain' => trim($domain, "'"),
                'result' => implode("\n", $output)
            ]
        ]);
    }
    
    // 端口扫描
    public function port(Request $request)
    {
        $host = $request->post('host', '');
        $port = $request->post('port', 80);
        
        if (empty($host)) {
            return json(['code' => -1, 'msg' => '请输入主机地址']);
        }
        
        $timeout = 2;
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        
        $isOpen = false;
        if ($fp) {
            $isOpen = true;
            fclose($fp);
        }
        
        return json([
            'code' => 0,
            'data' => [
                'host' => $host,
                'port' => $port,
                'is_open' => $isOpen,
                'message' => $isOpen ? '端口开放' : '端口关闭或无法访问'
            ]
        ]);
    }
}
