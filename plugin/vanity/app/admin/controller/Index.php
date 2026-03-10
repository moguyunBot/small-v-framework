<?php
namespace plugin\vanity\app\admin\controller;

use app\admin\controller\Base;
use support\Request;
use support\Response;

/**
 * Vanity 插件后台管理控制器
 */
class Index extends Base
{
    /**
     * 后台首页
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return view();
    }

}
