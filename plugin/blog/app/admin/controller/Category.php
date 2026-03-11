<?php
namespace plugin\blog\app\admin\controller;

use app\admin\controller\Base;
use plugin\blog\app\model\Category as CategoryModel;

class Category extends Base
{
    public function index()
    {
        $categories = CategoryModel::order('sort asc, id asc')->select();
        return $this->view(['categories' => $categories]);
    }

    public function add()
    {
        if ($this->isPost()) {
            try {
                $data = $this->post;
                if (empty($data['name'])) throw new \Exception('分类名称不能为空');
                $data['slug'] = CategoryModel::makeSlug($data['name']);
                CategoryModel::create($data);
                return success('添加成功', 'index');
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '添加失败');
            }
        }
        return $this->view();
    }

    public function edit()
    {
        $category = CategoryModel::find($this->get['id']);
        if ($this->isPost()) {
            try {
                $data = $this->post;
                unset($data['id']);
                $category->save($data);
                return success('保存成功', 'index');
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '保存失败');
            }
        }
        return $this->view(['category' => $category]);
    }

    public function del()
    {
        if ($this->isPost()) {
            try {
                $category = CategoryModel::find($this->post['id']);
                if (!$category) throw new \Exception('分类不存在');
                if ($category->post_count > 0) throw new \Exception('该分类下还有文章，请先移除文章');
                $category->delete();
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '删除失败');
            }
            return success('删除成功');
        }
    }
}
