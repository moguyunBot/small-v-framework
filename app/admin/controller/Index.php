<?php
namespace app\admin\controller;

use Webman\Captcha\CaptchaBuilder;
use Webman\Captcha\PhraseBuilder;
use app\admin\model\Admin;

class Index extends Base{
    protected $noNeedLogin = ['login','captcha'];
    
    public function index(){
        return $this->view('');
    }
    
    public function login(){
        $session = $this->request->session();
        if($this->request->method()=='POST'){
            try{
                validate([
                    'username|用户名'       =>  'require',
                    'password|密码'         =>  'require',
                    'captcha|验证码'        =>  'require',
                ])->check($this->post);
                if($this->post['captcha']!=session('captcha')){
                    throw new \Exception('验证码错误');
                }
                $admin = Admin::where(['username'=>$this->post['username']])->find();
                if(!$admin){
                    throw new \Exception('管理员不存在或密码错误1');
                }
                if(!password_verify($this->post['password'], $admin['password'])){
                    throw new \Exception('管理员不存在或密码错误2');
                }
                if($admin['status']!=1){
                    throw new \Exception('该管理员已禁用');
                }
                $session->set('admin',$admin->toArray());
            }catch(\Exception $e){
                $this->captcha();
                return error($e->getMessage()?:'登录失败');
            }
            return success('登录成功','index');
        }
        $session->delete('admin');
        return $this->view('');
    }
    
    public function captcha()
    {
        $builder = new PhraseBuilder(4, 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ');
        $captcha = new CaptchaBuilder(null, $builder);
        $captcha->build(120);
        $this->request->session()->set("captcha", strtolower($captcha->getPhrase()));
        $img_content = $captcha->get();
        return response($img_content, 200, ['Content-Type' => 'image/jpeg']);
    }
}