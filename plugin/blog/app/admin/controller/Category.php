<?php
namespace plugin\blog\app\admin\controller;

use app\admin\controller\Base;
use plugin\blog\app\model\Category as CategoryModel;
use support\Request;

class Category extends Base
{
    public function index(Request $request)
    {
        $categories = CategoryModel::order('sort asc, id asc')->select();
        return $this->view('', ['categories' => $categories]);
    }

    public function add(Request $request)
    {
        if ($request->isPost()) {
            try {
                $data = $request->post();
                if (empty($data['name'])) throw new \Exception('分类名称不能为空');
                $data['slug'] = CategoryModel::makeSlug($data['name']);
                CategoryModel::create($data);
                return success('添加成功', 'index');
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '添加失败');
            }
        }
        return $this->view('');
    }

    public function edit(Request $request)
    {
        $category = CategoryModel::find($request->get('id'));
        if ($request->isPost()) {
            try {
                $data = $request->post();
                unset($data['id']);
                $category->save($data);
                return success('保存成功', 'index');
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '保存失败');
            }
        }
        return $this->view('', ['category' => $category]);
    }

    public function del(Request $request)
    {
        if ($request->isPost()) {
            try {
                $category = CategoryModel::find($request->post('id'));
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
