<?php
namespace plugin\blog\app\controller;

use plugin\blog\app\model\Post;
use support\Request;

class Search
{
    public function index(Request $request)
    {
        $keyword = trim($request->get('q', ''));
        $page    = (int)$request->get('page', 1);
        $perPage = (int)(get_config('blog.posts_per_page') ?: 10);
        $posts   = Post::where('status', 1)
            ->whereLike('title|summary|content', "%{$keyword}%")
            ->order('create_time desc')
            ->paginate(['list_rows' => $perPage, 'page' => $page]);
        $categories = \plugin\blog\app\model\Category::order('sort asc')->select();
        return view('index/search', ['posts' => $posts, 'keyword' => $keyword, 'categories' => $categories]);
    }
}
