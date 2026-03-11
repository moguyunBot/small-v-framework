<?php
namespace plugin\blog\app\admin\controller;

use app\admin\controller\Base;
use plugin\blog\app\model\Comment as CommentModel;
use plugin\blog\app\model\Post as PostModel;

class Comment extends Base
{
    public function index()
    {
        $page   = (int)($this->get['page'] ?? 1);
        $status = $this->get['status'] ?? '';

        $query = CommentModel::with(['post'])->order('create_time desc');
        if ($status !== '') $query->where('status', (int)$status);

        $comments = $query->paginate(['list_rows' => 20, 'page' => $page]);
        return $this->view(['comments' => $comments, 'status' => $status]);
    }

    public function approve()
    {
        if ($this->isPost()) {
            $comment = CommentModel::find($this->post['id']);
            if ($comment && $comment->status != 1) {
                $comment->save(['status' => 1]);
                PostModel::where('id', $comment->post_id)->inc('comment_count');
            }
            return success('已通过');
        }
    }

    public function reject()
    {
        if ($this->isPost()) {
            $comment = CommentModel::find($this->post['id']);
            if ($comment) {
                if ($comment->status == 1) {
                    PostModel::where('id', $comment->post_id)->where('comment_count', '>', 0)->dec('comment_count');
                }
                $comment->save(['status' => 2]);
            }
            return success('已拒绝');
        }
    }

    public function del()
    {
        if ($this->isPost()) {
            try {
                $comment = CommentModel::find($this->post['id']);
                if (!$comment) throw new \Exception('评论不存在');
                if ($comment->status == 1) {
                    PostModel::where('id', $comment->post_id)->where('comment_count', '>', 0)->dec('comment_count');
                }
                CommentModel::where('parent_id', $comment->id)->delete();
                $comment->delete();
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '删除失败');
            }
            return success('删除成功');
        }
    }
}
