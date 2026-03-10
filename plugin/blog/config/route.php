<?php
use Webman\Route;

// 博客后台路由
Route::group('/app/blog/admin', function() {
    Route::get('/post/index',  [\plugin\blog\app\admin\controller\Post::class, 'index']);
    Route::get('/post/add',    [\plugin\blog\app\admin\controller\Post::class, 'add']);
    Route::post('/post/add',   [\plugin\blog\app\admin\controller\Post::class, 'add']);
    Route::get('/post/edit',   [\plugin\blog\app\admin\controller\Post::class, 'edit']);
    Route::post('/post/edit',  [\plugin\blog\app\admin\controller\Post::class, 'edit']);
    Route::post('/post/del',   [\plugin\blog\app\admin\controller\Post::class, 'del']);
    Route::get('/category/index',  [\plugin\blog\app\admin\controller\Category::class, 'index']);
    Route::get('/category/add',    [\plugin\blog\app\admin\controller\Category::class, 'add']);
    Route::post('/category/add',   [\plugin\blog\app\admin\controller\Category::class, 'add']);
    Route::get('/category/edit',   [\plugin\blog\app\admin\controller\Category::class, 'edit']);
    Route::post('/category/edit',  [\plugin\blog\app\admin\controller\Category::class, 'edit']);
    Route::post('/category/del',   [\plugin\blog\app\admin\controller\Category::class, 'del']);
    Route::get('/tag/index',  [\plugin\blog\app\admin\controller\Tag::class, 'index']);
    Route::get('/tag/add',    [\plugin\blog\app\admin\controller\Tag::class, 'add']);
    Route::post('/tag/add',   [\plugin\blog\app\admin\controller\Tag::class, 'add']);
    Route::get('/tag/edit',   [\plugin\blog\app\admin\controller\Tag::class, 'edit']);
    Route::post('/tag/edit',  [\plugin\blog\app\admin\controller\Tag::class, 'edit']);
    Route::post('/tag/del',   [\plugin\blog\app\admin\controller\Tag::class, 'del']);
    Route::get('/comment/index',    [\plugin\blog\app\admin\controller\Comment::class, 'index']);
    Route::post('/comment/approve', [\plugin\blog\app\admin\controller\Comment::class, 'approve']);
    Route::post('/comment/reject',  [\plugin\blog\app\admin\controller\Comment::class, 'reject']);
    Route::post('/comment/del',     [\plugin\blog\app\admin\controller\Comment::class, 'del']);
});

// 博客前台路由
Route::get('/blog', [\plugin\blog\app\controller\Index::class, 'index']);
Route::get('/blog/page/{page}', [\plugin\blog\app\controller\Index::class, 'index']);
Route::get('/blog/post/{slug}', [\plugin\blog\app\controller\Post::class, 'detail']);
Route::post('/blog/post/{slug}/comment', [\plugin\blog\app\controller\Post::class, 'comment']);
Route::get('/blog/category/{slug}', [\plugin\blog\app\controller\Category::class, 'index']);
Route::get('/blog/category/{slug}/page/{page}', [\plugin\blog\app\controller\Category::class, 'index']);
Route::get('/blog/tag/{slug}', [\plugin\blog\app\controller\Tag::class, 'index']);
Route::get('/blog/tag/{slug}/page/{page}', [\plugin\blog\app\controller\Tag::class, 'index']);
Route::get('/blog/archive', [\plugin\blog\app\controller\Archive::class, 'index']);
Route::get('/blog/search', [\plugin\blog\app\controller\Search::class, 'index']);
