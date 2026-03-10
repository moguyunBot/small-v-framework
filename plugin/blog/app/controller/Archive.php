<?php
namespace plugin\blog\app\controller;

use plugin\blog\app\model\Post;
use support\Request;

class Archive
{
    public function index(Request $request)
    {
        $posts = Post::where('status', 1)->order('create_time desc')->select();
        $archives   = [];
        foreach ($posts as $post) {
            $ym = date('Y年m月', strtotime($post->create_time));
            $archives[$ym][] = $post;
        }
        $categories = Post::where('status',1)->count() ? \plugin\blog\app\model\Category::order('sort asc')->select() : [];
        return view('index/archive', ['archives' => $archives, 'categories' => $categories]);
    }
}
