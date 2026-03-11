<?php
namespace plugin\blog\app\controller;

use plugin\blog\app\model\Category as CategoryModel;
use plugin\blog\app\model\Post;
use support\Request;

class Category
{
    public function index(Request $request, string $slug, int $page = 1)
    {
        $category = CategoryModel::where('slug', $slug)->find();
        if (!$category) return response('分类不存在', 404);
        $perPage = (int)(get_config('blog.posts_per_page') ?: 10);
        $posts = Post::where('category_id', $category->id)->where('status', 1)
            ->order('is_top desc, create_time desc')
            ->paginate(['list_rows' => $perPage, 'page' => $page]);
        $categories = CategoryModel::order('sort asc')->select();
        return view('index/category', ['category' => $category, 'posts' => $posts, 'page' => $page, 'categories' => $categories]);
    }
}
