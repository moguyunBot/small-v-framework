<?php
namespace app\admin\controller;

use app\admin\model\Rule;

class Role extends Base{
    public function index(){
        $where = [];
        $roles = $this->model->where($where)->paginate(['list_rows'=>20,'query'=>$this->get]);
        return $this->view('',['roles'=>$roles]);
    }
 
    
    public function add(){
        if($this->isPost()){
            try{
                validate([
                    'name|用户组名称'       =>  'require|unique:roles',
                    'rules|权限'            =>  'require'
                ])->useZh()->check($this->post);
                
                $this->post['rules'] = join(',',$this->post['rules']);
                $this->model::create($this->post);
                
            }catch(\Exception $e){
                return error($e->getMessage()?:'添加失败');
            }
            return success('添加成功','index');
        }
        $rules = Rule::field('id,pid parent,title text')->where(['status'=>1])->order('sort asc,id asc')->select()->map(function ($v) {
            $v['parent'] = $v['parent'] ?: '#';
            return $v;
        });
        return $this->view('',['rules'=>$rules]);
    }
    
    public function edit(){
        $role = $this->model::find($this->get['id']);
        if($this->isPost()){
            try{
                validate([
                    'name|角色名称'         =>  'require|unique:roles',
                    'rules|权限'            =>  'require'
                ])->useZh()->check($this->post);
                
                $this->post['rules'] = join(',',$this->post['rules']);
                $role->replace()->save($this->post);
            }catch(\Exception $e){
                return error($e->getMessage()?:'修改失败');
            }
            return success('修改成功','index');
        }
        $rules = Rule::field('id,pid parent,title text')->where(['status'=>1])->order('sort asc,id asc')->select()->map(function ($v) use ($role) {
            $v['parent'] = $v['parent'] ?: '#';
            if ($role['rules'] == '*') {
                $v['state'] = [
                    'selected'      =>  true
                ];
            } else if (!Rule::where(['pid' => $v['id']])->find() && in_array($v['id'], explode(',', $role['rules']))) {
                $v['state'] = [
                    'selected'      =>  true
                ];
            } else {
                $v['state'] = [
                    'selected'      =>  false
                ];
            }
            return $v;
        });
        return $this->view('',['rules'=>$rules,'role'=>$role]);
    }
    
    public function del(){
        if($this->isPost()){
            try{
                $role = $this->model::find($this->post['id']);
                if(!$role){
                    throw new \Exception('角色不存在');
                }
                $role->delete();
            }catch(\Exception $e){
                return error($e->getMessage()?:'删除失败');
            }
            return success('删除成功');
        }
    }
}
