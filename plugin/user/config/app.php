<?php
return [
    'debug'             => true,
    'controller_suffix' => '',
    'controller_reuse'  => false,
    'enable'            => true,
    'identifier'        => 'user',
    'name'              => '用户系统',
    'version'           => '1.0.0',
    'author'            => '',
    'description'       => '前台用户注册、登录、个人中心，后台用户管理',
    'icon'              => 'mdi mdi-account-group',
    'settings'          => [
        [
            'group_key'   => 'user',
            'group_title' => '用户设置',
            'configs'     => [
                ['config_key' => 'user.register_enable', 'config_title' => '允许注册',     'config_type' => 'radio',  'config_value' => '1', 'config_options' => '[{"key":"1","name":"允许"},{"key":"0","name":"关闭"}]'],
                ['config_key' => 'user.login_type',      'config_title' => '登录方式',     'config_type' => 'radio',  'config_value' => 'email', 'config_options' => '[{"key":"email","name":"邮箱"},{"key":"mobile","name":"手机号"}]'],
                ['config_key' => 'user.register_bonus',  'config_title' => '注册赠送余额', 'config_type' => 'number', 'config_value' => '0', 'config_desc' => '单位：元，0表示不赠送'],
            ],
        ],
    ],
];
