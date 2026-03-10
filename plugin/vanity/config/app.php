<?php
return [
    'debug' => true,
    'controller_suffix' => '',
    'controller_reuse' => false,
    'enable'      => true,
    'identifier'  => 'vanity',
    'name'        => 'ETH Vanity地址生成器',
    'version'     => '1.0.0',
    'author'      => 'System',
    'description' => '生成以太坊靓号地址（自定义前缀/后缀）',
    'icon'        => 'mdi mdi-ethereum',
    'settings'        =>  [
        //这里是插件的配置项
        'group_key'     =>  'basic',
        'group_title'   =>  '基本设置',
        'configs'       =>  [
            [
                'config_key'        =>  'title',
                'config_title'      =>  '网站标题',
                'config_type'       =>  'text',
                'config_value'      =>  '默认值',
                'config_options'    =>  [
                    [
                        'key'   =>  1,
                        'name'  =>  '是'
                    ],
                    [
                        'key'   =>  2,
                        'name'  =>  '否'
                    ]
                ],
                'config_desc'       =>  '介绍',
                'sort'              =>  1,
            ]
        ]
    ]
];
