<?php
return [
    'menus' => [
        [
            'title'   => '文章管理',
            'href'    => '/app/blog/admin/Post/index',
            'icon'    => 'mdi mdi-file-document',
            'sort'    => 1,
            'is_menu' => 1,
        ],
        [
            'title'   => '分类管理',
            'href'    => '/app/blog/admin/Category/index',
            'icon'    => 'mdi mdi-folder',
            'sort'    => 2,
            'is_menu' => 1,
        ],
        [
            'title'   => '标签管理',
            'href'    => '/app/blog/admin/Tag/index',
            'icon'    => 'mdi mdi-tag',
            'sort'    => 3,
            'is_menu' => 1,
        ],
        [
            'title'   => '评论管理',
            'href'    => '/app/blog/admin/Comment/index',
            'icon'    => 'mdi mdi-comment',
            'sort'    => 4,
            'is_menu' => 1,
        ],
    ],
    'permissions' => [],
];
