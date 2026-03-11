<?php
namespace plugin\blog\app\admin\controller;

use app\admin\controller\Base;
use plugin\blog\app\model\Tag as TagModel;

class Tag extends Base
{
    public function index()
    {
        $tags = TagModel::order('post_count desc, id asc')->select();
        return $this->view(['tags' => $tags]);
    }

    public function add()
    {
        if ($this->isPost()) {
            try {
                $name = trim($this->post['name'] ?? '');
                if (!$name) throw new \Exception('标签名称不能为空');
                TagModel::create(['name' => $name, 'slug' => TagModel::makeSlug($name)]);
                return success('添加成功', 'index');
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '添加失败');
            }
        }
        return $this->view();
    }

    public function edit()
    {
        $tag = TagModel::find($this->get['id']);
        if ($this->isPost()) {
            try {
                $data = $this->post;
                unset($data['id']);
                $tag->save($data);
                return success('保存成功', 'index');
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '保存失败');
            }
        }
        return $this->view(['tag' => $tag]);
    }

    public function del()
    {
        if ($this->isPost()) {
            try {
                $tag = TagModel::find($this->post['id']);
                if (!$tag) throw new \Exception('标签不存在');
                \think\facade\Db::table('blog_post_tags')->where('tag_id', $tag->id)->delete();
                $tag->delete();
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '删除失败');
            }
            return success('删除成功');
        }
    }
}
