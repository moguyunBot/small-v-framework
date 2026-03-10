<?php
/**
 * Vanity 插件菜单配置
 * 安装时自动注册到后台菜单
 */

return [
    // 插件菜单（安装时自动注入 admin_rules 表）
    'menus' => [
        [
            'title'    => 'Vanity生成器',
            'href'     => '/plugin/vanity',
            'icon'     => 'mdi mdi-ethereum',
            'sort'     => 10,
            // 子菜单
            'children' => [
                [
                    'title' => '生成地址',
                    'href'  => '/plugin/vanity/index',
                    'icon'  => 'mdi mdi-play-circle',
                    'sort'  => 1,
                ],
                [
                    'title' => '历史记录',
                    'href'  => '/plugin/vanity/history',
                    'icon'  => 'mdi mdi-history',
                    'sort'  => 2,
                ],
            ],
        ],
    ],
    
    // 权限节点（用于角色分配时勾选）
    // 注意：实际权限节点由menus自动生成，这里可以定义额外的非菜单权限
    'permissions' => [
        [
            'title' => '执行生成',
            'href'  => '/plugin/vanity/generate',
        ],
        [
            'title' => '导出结果',
            'href'  => '/plugin/vanity/export',
        ],
    ],
];
