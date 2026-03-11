<?php
return [
    [
        'title'   => '用户管理',
        'href'    => '/app/user/admin/Member/index',
        'icon'    => 'mdi mdi-account-group',
        'sort'    => 1,
        'is_menu' => 1,
        'children' => [
            ['title' => '添加用户',   'href' => '/app/user/admin/Member/add',    'is_menu' => 0],
            ['title' => '编辑用户',   'href' => '/app/user/admin/Member/edit',   'is_menu' => 0],
            ['title' => '删除用户',   'href' => '/app/user/admin/Member/del',    'is_menu' => 0],
            ['title' => '调整余额',   'href' => '/app/user/admin/Member/balance', 'is_menu' => 0],
        ],
    ],
];
