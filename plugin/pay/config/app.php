<?php
return [
    'debug'             => true,
    'controller_suffix' => '',
    'controller_reuse'  => false,
    'enable'            => true,
    'identifier'        => 'pay',
    'name'              => '支付插件',
    'version'           => '1.0.0',
    'author'            => '',
    'require'           => ['user'],
    'description'       => '支付宝、微信支付统一封装，支付成功触发 payment.paid 事件',
    'icon'        => 'mdi mdi-credit-card',
    'settings'    => [
        [
            'group_key'   => 'pay_alipay',
            'group_title' => '支付宝配置',
            'configs'     => [
                ['config_key' => 'app_id',       'config_title' => 'AppID',           'config_type' => 'text',     'config_value' => ''],
                ['config_key' => 'private_key',  'config_title' => '应用私钥',         'config_type' => 'textarea', 'config_value' => ''],
                ['config_key' => 'public_key',   'config_title' => '支付宝公钥',       'config_type' => 'textarea', 'config_value' => ''],
                ['config_key' => 'notify_url',   'config_title' => '异步通知地址',     'config_type' => 'text',     'config_value' => ''],
                ['config_key' => 'return_url',   'config_title' => '同步跳转地址',     'config_type' => 'text',     'config_value' => ''],
                ['config_key' => 'sandbox',      'config_title' => '沙箱模式',         'config_type' => 'radio',    'config_value' => '0', 'config_options' => '[{"key":"1","name":"开启"},{"key":"0","name":"关闭"}]'],
            ],
        ],
        [
            'group_key'   => 'pay_wechat',
            'group_title' => '微信支付配置',
            'configs'     => [
                ['config_key' => 'app_id',     'config_title' => 'AppID',       'config_type' => 'text', 'config_value' => ''],
                ['config_key' => 'mch_id',    'config_title' => '商户号',       'config_type' => 'text', 'config_value' => ''],
                ['config_key' => 'api_key',   'config_title' => 'API Key(v3)', 'config_type' => 'text', 'config_value' => ''],
                ['config_key' => 'notify_url','config_title' => '异步通知地址', 'config_type' => 'text', 'config_value' => ''],
                ['config_key' => 'cert_path', 'config_title' => '证书路径(apiclient_cert.pem)', 'config_type' => 'text', 'config_value' => ''],
                ['config_key' => 'key_path',  'config_title' => '证书密钥路径(apiclient_key.pem)', 'config_type' => 'text', 'config_value' => ''],
            ],
        ],
    ],
];
