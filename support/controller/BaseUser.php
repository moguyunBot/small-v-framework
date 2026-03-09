<?php
namespace support\controller;

use support\View;

/**
 * 前台基础控制器
 * 所有前台控制器（包括插件）都应该继承此类
 */
class BaseUser
{
    /**
     * @var Model
     */
    protected $model = null;

    /**
     * 无需登录的方法
     * @var array
     */
    protected $noNeedLogin = [];
    
    public $post, $get, $request, $controller, $action;
    
    public function __construct()
    {
        $this->request = $request = request();
        $this->post = $request->post();
        $this->get = $request->get();
        $this->controller = $request->controller;
        $this->action = $request->action;
        
        // 自动加载模型
        if (class_exists(str_replace('controller', 'model', $this->controller))) {
            $class = str_replace('controller', 'model', $this->controller);
            $this->model = new $class();
        }
        
        // 获取当前登录用户
        $user = $this->getUser();
        if ($user) {
            View::assign('user', $user);
        }
    }
    
    /**
     * 判断是否为 POST 请求
     */
    public function isPost()
    {
        return $this->request->method() === 'POST';
    }
    
    /**
     * 获取当前登录用户
     */
    protected function getUser()
    {
        return $this->request->session()->get('user');
    }
    
    /**
     * 获取当前登录用户ID
     */
    protected function getUserId()
    {
        return $this->request->session()->get('user_id');
    }
    
    /**
     * 检查是否登录
     */
    protected function isLogin()
    {
        return !empty($this->getUserId());
    }
    
    /**
     * 要求登录
     */
    protected function requireLogin()
    {
        if (!$this->isLogin()) {
            if ($this->isPost()) {
                return json(['code' => 0, 'msg' => '请先登录']);
            }
            return redirect('/app/user/login');
        }
    }
}
