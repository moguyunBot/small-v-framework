<?php
namespace app\admin\controller;

class Rule extends Base{
    public function index(){
        $rules = $this->model->order('sort asc,id desc')->select();
        $menus = $this->recursion_title($rules);
        return $this->view('',['menus'=>$menus]);
    }
    
    
    public function recursion_title($list, $pid = 0, $level = 0)
    {
        $arr = [];
        foreach ($list as $v) {
            if ($v['pid'] == $pid) {
                $v['title'] = str_repeat('|——', $level) . $v['title'];
                $arr[] = $v;
                $arr = array_merge($arr, $this->recursion_title($list, $v['id'], $level + 1));
            }
        }
        return $arr;
    } 
    
    public function add(){
        if($this->isPost()){
            try{
                validate([
                    'title'         =>  'require',
                    'pid'           =>  'require',
                    'sort'          =>  'require',
                ])->check($this->post);
                $this->model::create($this->post);
            }catch(\Exception $e){
                return error($e->getMessage()?:'添加失败');
            }
            return success('添加成功', 'index');
        }
        
        $rules = $this->model->order('sort asc,id desc')->select();
        $menus = $this->recursion_title($rules);
        return $this->view('',['menus'=>$menus]);
    }
    
    public function edit(){
        $rule = $this->model->find($this->get['id']);
        if($this->isPost()){
            try{
                validate([
                    'title'         =>  'require',
                    'pid'           =>  'require',
                    'sort'          =>  'require',
                ])->check($this->post);
                $rule->replace()->save($this->post);
            }catch(\Exception $e){
                return error($e->getMessage()?:'修改失败');
            }
            return success('修改成功', 'index');
        }
        $rules = $this->model->order('sort asc,id desc')->select();
        $menus = $this->recursion_title($rules);
        return $this->view('',['rule'=>$rule,'menus'=>$menus]);
    }
    
    public function del(){
        if($this->isPost()){
            try{
                $rule = $this->model::find($this->post['id']);
                if(!$rule){
                    throw new \Exception('菜单不存在');
                }
                if($this->model::where(['pid'=>$rule['id']])->find()){
                    throw new \Exception('该菜单存在子节点');
                }
                $rule->delete();
            }catch(\Exception $e){
                return error($e->getMessage()?:'删除失败');
            }
            return success('删除成功');
        }
    }
}
