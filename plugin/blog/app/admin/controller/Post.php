<?php
namespace plugin\blog\app\admin\controller;

use app\admin\controller\Base;
use plugin\blog\app\model\Post as PostModel;
use plugin\blog\app\model\Category;
use plugin\blog\app\model\Tag;

class Post extends Base
{
    public function index()
    {
        $page    = (int)($this->get['page'] ?? 1);
        $keyword = trim($this->get['keyword'] ?? '');
        $status  = $this->get['status'] ?? '';

        $query = PostModel::with(['category'])->order('is_top desc, create_time desc');
        if ($keyword) $query->whereLike('title', "%{$keyword}%");
        if ($status !== '') $query->where('status', (int)$status);

        $posts = $query->paginate(['list_rows' => 15, 'page' => $page]);
        return $this->view(['posts' => $posts, 'keyword' => $keyword, 'status' => $status]);
    }

    public function add()
    {
        $categories = Category::order('sort asc, id asc')->select();
        $tags       = Tag::order('name asc')->select();

        if ($this->isPost()) {
            try {
                $data           = $this->post;
                $data['slug']   = PostModel::makeSlug($data['title']);
                $data['status'] = (int)($data['status'] ?? 1);
                $data['is_top'] = (int)($data['is_top'] ?? 0);
                $tagIds = $data['tag_ids'] ?? [];
                unset($data['tag_ids']);
                $post = PostModel::create($data);
                if ($tagIds) {
                    $post->tags()->attach($tagIds);
                    Tag::whereIn('id', $tagIds)->inc('post_count');
                }
                if (!empty($data['category_id'])) {
                    Category::where('id', $data['category_id'])->inc('post_count');
                }
                return success('发布成功', 'index');
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '发布失败');
            }
        }
        return $this->view(['categories' => $categories, 'tags' => $tags]);
    }

    public function edit()
    {
        $post       = PostModel::with(['tags'])->find($this->get['id']);
        $categories = Category::order('sort asc, id asc')->select();
        $tags       = Tag::order('name asc')->select();
        $postTagIds = array_column($post->tags->toArray(), 'id');

        if ($this->isPost()) {
            try {
                $data           = $this->post;
                $data['status'] = (int)($data['status'] ?? 1);
                $data['is_top'] = (int)($data['is_top'] ?? 0);
                $tagIds = $data['tag_ids'] ?? [];
                unset($data['tag_ids'], $data['id']);
                $post->save($data);
                $post->tags()->sync($tagIds);
                // 只更新受影响的 tag 计数，避免全表扫描
                $allAffectedIds = array_unique(array_merge($postTagIds, $tagIds));
                foreach ($allAffectedIds as $tid) {
                    Tag::where('id', $tid)->save(['post_count' => \think\facade\Db::table('blog_post_tags')->where('tag_id', $tid)->count()]);
                }
                return success('保存成功', 'index');
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '保存失败');
            }
        }
        return $this->view(['post' => $post, 'categories' => $categories, 'tags' => $tags, 'postTagIds' => $postTagIds]);
    }

    public function del()
    {
        if ($this->isPost()) {
            try {
                $post = PostModel::find($this->post['id']);
                if (!$post) throw new \Exception('文章不存在');
                $post->tags()->detach();
                $post->delete();
                Category::where('id', $post->category_id)->where('post_count', '>', 0)->dec('post_count');
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '删除失败');
            }
            return success('删除成功');
        }
    }
}
