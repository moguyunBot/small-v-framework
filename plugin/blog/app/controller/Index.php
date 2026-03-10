<?php
namespace plugin\blog\app\controller;

use plugin\blog\app\model\Post;
use plugin\blog\app\model\Category;
use support\Request;

class Index
{
    public function index(Request $request, int $page = 1)
    {
        $perPage = (int)(get_config('blog.posts_per_page') ?: 10);
        $posts = Post::with(['category'])
            ->where('status', 1)
            ->order('is_top desc, create_time desc')
            ->paginate(['list_rows' => $perPage, 'page' => $page]);
        $categories = Category::order('sort asc')->select();
        return view('index/index', [
            'posts'      => $posts,
            'categories' => $categories,
            'page'       => $page,
        ]);
    }
}
