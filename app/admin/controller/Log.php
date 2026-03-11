<?php
namespace app\admin\controller;

use app\admin\model\AdminLoginLog;
use app\admin\model\AdminOpLog;

/**
 * 日志管理
 */
class Log extends Base
{
    /**
     * 登录日志
     */
    public function loginLog()
    {
        $where = [];
        $keyword = $this->get['keyword'] ?? '';
        if ($keyword) {
            $where[] = ['username', 'like', '%' . $keyword . '%'];
        }
        $status = $this->get['status'] ?? '';
        if ($status !== '') {
            $where[] = ['status', '=', (int)$status];
        }
        $logs = AdminLoginLog::where($where)
            ->order('id desc')
            ->paginate(['list_rows' => 20, 'query' => $this->get]);
        return $this->view(['logs' => $logs, 'keyword' => $keyword, 'status' => $status]);
    }

    /**
     * 操作日志
     */
    public function opLog()
    {
        $where = [];
        $keyword = $this->get['keyword'] ?? '';
        if ($keyword) {
            $where[] = ['username', 'like', '%' . $keyword . '%'];
        }
        $logs = AdminOpLog::where($where)
            ->order('id desc')
            ->paginate(['list_rows' => 20, 'query' => $this->get]);
        return $this->view(['logs' => $logs, 'keyword' => $keyword]);
    }

    /**
     * 清空登录日志
     */
    public function clearLoginLog()
    {
        if (!$this->isPost()) return error('非法请求');
        AdminLoginLog::where('id', '>', 0)->delete();
        return success('清空成功');
    }

    /**
     * 清空操作日志
     */
    public function clearOpLog()
    {
        if (!$this->isPost()) return error('非法请求');
        AdminOpLog::where('id', '>', 0)->delete();
        return success('清空成功');
    }
}
