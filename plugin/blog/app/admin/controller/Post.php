<?php
namespace plugin\blog\app\admin\controller;

use app\admin\controller\Base;
use plugin\blog\app\model\Post as PostModel;
use plugin\blog\app\model\Category;
use plugin\blog\app\model\Tag;
use support\Request;

class Post extends Base
{
    public function index(Request $request)
    {
        $page    = (int)$request->get('page', 1);
        $keyword = trim($request->get('keyword', ''));
        $status  = $request->get('status', '');

        $query = PostModel::with(['category'])->order('is_top desc, create_time desc');
        if ($keyword) $query->whereLike('title', "%{$keyword}%");
        if ($status !== '') $query->where('status', (int)$status);

        $posts = $query->paginate(['list_rows' => 15, 'page' => $page]);
        return $this->view('', ['posts' => $posts, 'keyword' => $keyword, 'status' => $status]);
    }

    public function add(Request $request)
    {
        $categories = Category::order('sort asc, id asc')->select();
        $tags       = Tag::order('name asc')->select();

        if ($request->isPost()) {
            try {
                $data = $request->post();
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
        return $this->view('', ['categories' => $categories, 'tags' => $tags]);
    }

    public function edit(Request $request)
    {
        $post       = PostModel::with(['tags'])->find($request->get('id'));
        $categories = Category::order('sort asc, id asc')->select();
        $tags       = Tag::order('name asc')->select();
        $postTagIds = array_column($post->tags->toArray(), 'id');

        if ($request->isPost()) {
            try {
                $data = $request->post();
                $data['status'] = (int)($data['status'] ?? 1);
                $data['is_top'] = (int)($data['is_top'] ?? 0);
                $tagIds = $data['tag_ids'] ?? [];
                unset($data['tag_ids'], $data['id']);
                $post->save($data);
                $post->tags()->sync($tagIds);
                Tag::all()->each(function($t) {
                    $t->save(['post_count' => \think\facade\Db::table('blog_post_tags')->where('tag_id', $t->id)->count()]);
                });
                return success('保存成功', 'index');
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '保存失败');
            }
        }
        return $this->view('', ['post' => $post, 'categories' => $categories, 'tags' => $tags, 'postTagIds' => $postTagIds]);
    }

    public function del(Request $request)
    {
        if ($request->isPost()) {
            try {
                $post = PostModel::find($request->post('id'));
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
