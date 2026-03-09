<?php
return [
    // 信任的代理服务器
    'proxies' => '*', // 信任所有代理，生产环境建议指定具体 IP
    
    // 真实 IP 的 header 名称（按优先级）
    'headers' => [
        'HTTP_X_REAL_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR',
    ],
];
