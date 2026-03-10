<?php
namespace plugin\blog\app\controller;

use plugin\blog\app\model\Tag as TagModel;
use plugin\blog\app\model\Post;
use support\Request;

class Tag
{
    public function index(Request $request, string $slug, int $page = 1)
    {
        $tag = TagModel::where('slug', $slug)->find();
        if (!$tag) return response('标签不存在', 404);
        $perPage = (int)(get_config('blog.posts_per_page') ?: 10);
        $posts = $tag->posts()->where('status', 1)
            ->order('create_time desc')
            ->paginate(['list_rows' => $perPage, 'page' => $page]);
        $categories = \plugin\blog\app\model\Category::order('sort asc')->select();
        return view('index/tag', ['tag' => $tag, 'posts' => $posts, 'page' => $page, 'categories' => $categories]);
    }
}
