<?php
/**
 * Vanity 插件配置
 */

return [
    'enable'      => true,
    'identifier'  => 'vanity',
    'name'        => 'ETH Vanity地址生成器',
    'version'     => '1.0.0',
    'author'      => 'System',
    'description' => '生成以太坊靓号地址（自定义前缀/后缀）',
    'icon'        => 'mdi mdi-ethereum',
    
    // 配置项
    'settings'    => [
        'max_threads' => [
            'type'    => 'number',
            'title'   => '最大线程数',
            'default' => 4,
            'min'     => 1,
            'max'     => 16,
        ],
        'timeout' => [
            'type'    => 'number',
            'title'   => '超时时间（秒）',
            'default' => 60,
            'min'     => 10,
            'max'     => 300,
        ],
    ],
];
