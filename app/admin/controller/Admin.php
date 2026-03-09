<?php
namespace app\admin\controller;

use app\admin\model\{Role,AdminRole};

/**
 * 管理员管理
 */
class Admin extends Base{
    /**
     * 管理员列表
     * @return \support\Response
     */
    public function index(){
        $where = [];
        $admins = $this->model->where($where)->paginate(['list_rows'=>20,'query'=>$this]);
        // print_r($admins);
        return $this->view('',['admins'=>$admins]);
    }
    
    /**
     * 添加管理员
     * @return \support\Response
     */
    public function add(){
        if($this->isPost()){
            $this->model::startTrans();
            try{
                validate([
                    'role_ids|角色'         =>  'require',
                    'username|用户名'       =>  'require|unique:admins',
                    'password|登录密码'     =>  'require|length:5,25',
                ])->useZh()->check($this->post);
                $this->post['password'] = password_hash($this->post['password'],PASSWORD_DEFAULT);
                $admin = $this->model::create($this->post);
                $roles = [];
                foreach($this->post['role_ids'] as $v){
                    $roles[] = [
                        'role_id'       =>  $v,
                        'admin_id'      =>  $admin['id'],
                    ];
                }
                AdminRole::saveAll($roles);
                $this->model::commit();
            }catch(\Exception $e){
                $this->model::rollback();
                return error($e->getMessage()?:'添加失败');
            }
            return success('添加成功','index');
        }
        $roles = Role::where([
            ['rules','<>','*'],
            ['status','=',1],
        ])->order('id desc')->select();
        return $this->view('',['roles'=>$roles]);
    }
    
    /**
     * 编辑管理员
     * @return \support\Response
     */
    public function edit(){
        $admin = $this->model::find($this->get['id']);
        $admin->hidden(['password']);
        $admin->append(['role_ids']);
        if($this->isPost()){
            $this->model::startTrans();
            try{
                validate([
                    'role_ids|角色'         =>  'require',
                    'username|用户名'       =>  'require|unique:admins',
                    'nickname|昵称'         =>  'require',
                ])->useZh()->check($this->post);
                if(!empty($this->post['password'])){
                    $this->post['password'] = password_hash($this->post['password'],PASSWORD_DEFAULT);
                }
                $admin->replace()->save($this->post);
                AdminRole::where(['admin_id'=>$admin['id']])->delete();
                $roles = [];
                foreach($this->post['role_ids'] as $v){
                    $roles[] = [
                        'role_id'       =>  $v,
                        'admin_id'      =>  $admin['id'],
                    ];
                }
                AdminRole::saveAll($roles);
                $this->model::commit();
            }catch(\Exception $e){
                $this->model::rollback();
                return error($e->getMessage()?:'修改失败');
            }
            return success('修改成功','index');
        }
        $roles = Role::where([
            ['status','=',1],
        ])->order('id desc')->select();
        return $this->view('',['roles'=>$roles,'admin'=>$admin]);
    }
    
    /**
     * 删除管理员
     * @return \support\Response
     */
    public function del(){
        if($this->isPost()){
            $this->model::startTrans();
            try{
                $admin = $this->model::find($this->post['id']);
                if(!$admin){
                    throw new \Exception('管理员不存在');
                }
                $role_ids = AdminRole::where(['admin_id'=>$admin['id']])->column('role_id');
                $roles = Role::where([['id','in',$role_ids]])->select();
                foreach($roles as $role){
                    if($role['rules']=='*'){
                        throw new \Exception('该管理员有超管权限,故不能删除');
                    }
                }
                $admin->delete();
                AdminRole::where(['admin_id'=>$admin['id']])->delete();
                $this->model::commit();
            }catch(\Exception $e){
                $this->model::rollback();
                return error($e->getMessage()?:'删除失败');
            }
            return success('删除成功');
        }
    }
    
    /**
     * 个人信息
     * @return \support\Response
     */
    public function profile(){
        $admin = admin();
        if($this->isPost()){
            try{
                validate([
                    'nickname|昵称'  =>  'require',
                    'email|邮箱'     =>  'email',
                ])->useZh()->check($this->post);
                
                $adminModel = $this->model::find($admin['id']);
                $adminModel->nickname = $this->post['nickname'];
                $adminModel->email = $this->post['email'] ?? '';
                $adminModel->save();
                
                // 更新 session
                session()->set('admin', $adminModel->toArray());
                
            }catch(\Exception $e){
                return error($e->getMessage()?:'修改失败');
            }
            return success('修改成功');
        }
        return $this->view('',['admin'=>$admin]);
    }
    
    /**
     * 修改密码
     * @return \support\Response
     */
    public function password(){
        if($this->isPost()){
            try{
                validate([
                    'old_password|原密码'       =>  'require',
                    'new_password|新密码'       =>  'require|length:5,25',
                    'confirm_password|确认密码' =>  'require|confirm:new_password',
                ])->useZh()->check($this->post);
                
                $admin = $this->model::find(admin('id'));
                
                // 验证原密码
                if(!password_verify($this->post['old_password'], $admin['password'])){
                    throw new \Exception('原密码错误');
                }
                
                // 更新密码
                $admin->password = password_hash($this->post['new_password'], PASSWORD_DEFAULT);
                $admin->save();
                
                // 清除登录信息
                session()->forget('admin');
                
            }catch(\Exception $e){
                return error($e->getMessage()?:'修改失败');
            }
            return success('密码修改成功，请重新登录','/admin/index/login');
        }
        return $this->view('');
    }
}
