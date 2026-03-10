<?php
namespace plugin\blog\app\controller;

use plugin\blog\app\model\Post as PostModel;
use plugin\blog\app\model\Comment;
use support\Request;

class Post
{
    public function detail(Request $request, string $slug)
    {
        $post = PostModel::with(['category', 'tags'])->where('slug', $slug)->where('status', 1)->find();
        if (!$post) {
            return response('文章不存在', 404);
        }
        PostModel::where('id', $post->id)->inc('view_count');
        $comments   = Comment::where('post_id', $post->id)->where('status', 1)->where('parent_id', 0)->order('create_time asc')->select();
        $categories = \plugin\blog\app\model\Category::order('sort asc')->select();
        return view('index/post', ['post' => $post, 'comments' => $comments, 'categories' => $categories]);
    }

    public function comment(Request $request, string $slug)
    {
        $post = PostModel::where('slug', $slug)->find();
        if (!$post) return json(['code' => 1, 'msg' => '文章不存在']);
        if (!get_config('blog.comment_enable')) return json(['code' => 1, 'msg' => '评论已关闭']);
        $data = [
            'post_id'  => $post->id,
            'nickname' => trim($request->post('nickname', '')),
            'email'    => trim($request->post('email', '')),
            'content'  => trim($request->post('content', '')),
            'ip'       => $request->getRemoteIp(),
            'status'   => get_config('blog.comment_audit') ? 0 : 1,
        ];
        if (!$data['nickname'] || !$data['content']) return json(['code' => 1, 'msg' => '昵称和内容不能为空']);
        Comment::create($data);
        if ($data['status'] == 1) PostModel::where('id', $post->id)->inc('comment_count');
        return json(['code' => 0, 'msg' => $data['status'] ? '评论成功' : '评论已提交，等待审核']);
    }
}
