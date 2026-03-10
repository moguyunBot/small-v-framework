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
        // 获取统计数据
        $stats = [
            'total_links' => 0,      // 总链接数
            'today_links' => 0,      // 今日生成
            'total_visits' => 0,     // 总访问量
            'active_links' => 0,     // 有效链接
        ];

        // TODO: 从数据库获取真实统计数据

        return view('admin/index/index', [
            'stats' => $stats
        ]);
    }

    /**
     * 链接列表
     * @param Request $request
     * @return Response
     */
    public function list(Request $request): Response
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $keyword = $request->get('keyword', '');
        $status = $request->get('status', '');

        // TODO: 从数据库获取链接列表
        $list = [];
        $total = 0;

        if ($request->isAjax()) {
            return json([
                'code' => 0,
                'msg' => '获取成功',
                'data' => $list,
                'count' => $total
            ]);
        }

        return view('admin/index/list');
    }

    /**
     * 添加链接
     * @param Request $request
     * @return Response
     */
    public function add(Request $request): Response
    {
        if ($request->isPost()) {
            $data = $request->post();

            // 验证数据
            if (empty($data['url'])) {
                return json(['code' => 1, 'msg' => 'URL不能为空']);
            }

            if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
                return json(['code' => 1, 'msg' => 'URL格式不正确']);
            }

            // TODO: 保存到数据库

            return json(['code' => 0, 'msg' => '添加成功']);
        }

        return view('admin/index/add');
    }

    /**
     * 编辑链接
     * @param Request $request
     * @return Response
     */
    public function edit(Request $request): Response
    {
        $id = $request->get('id');

        if ($request->isPost()) {
            $data = $request->post();

            // TODO: 更新数据库

            return json(['code' => 0, 'msg' => '更新成功']);
        }

        // TODO: 获取链接详情
        $info = [];

        return view('admin/index/edit', ['info' => $info]);
    }

    /**
     * 删除链接
     * @param Request $request
     * @return Response
     */
    public function delete(Request $request): Response
    {
        $id = $request->post('id');

        if (empty($id)) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        // TODO: 删除数据库记录

        return json(['code' => 0, 'msg' => '删除成功']);
    }

    /**
     * 批量删除
     * @param Request $request
     * @return Response
     */
    public function batchDelete(Request $request): Response
    {
        $ids = $request->post('ids', []);

        if (empty($ids)) {
            return json(['code' => 1, 'msg' => '请选择要删除的记录']);
        }

        // TODO: 批量删除

        return json(['code' => 0, 'msg' => '批量删除成功']);
    }

    /**
     * 修改状态
     * @param Request $request
     * @return Response
     */
    public function status(Request $request): Response
    {
        $id = $request->post('id');
        $status = $request->post('status', 0);

        if (empty($id)) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        // TODO: 更新状态

        return json(['code' => 0, 'msg' => '状态更新成功']);
    }

    /**
     * 访问统计
     * @param Request $request
     * @return Response
     */
    public function stats(Request $request): Response
    {
        // TODO: 获取访问统计数据
        $stats = [
            'daily_visits' => [],
            'top_links' => [],
            'referer_stats' => [],
        ];

        return view('admin/index/stats', ['stats' => $stats]);
    }

    /**
     * 插件设置
     * @param Request $request
     * @return Response
     */
    public function setting(Request $request): Response
    {
        if ($request->isPost()) {
            $config = $request->post();

            // TODO: 保存插件配置

            return json(['code' => 0, 'msg' => '保存成功']);
        }

        // TODO: 获取插件配置
        $config = [];

        return view('admin/index/setting', ['config' => $config]);
    }
}
