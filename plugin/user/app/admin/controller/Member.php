<?php
namespace plugin\user\app\admin\controller;

use app\admin\controller\Base;
use plugin\user\app\model\Member as MemberModel;
use Webman\Event\Event;

class Member extends Base
{
    public function index()
    {
        $page    = (int)($this->get['page'] ?? 1);
        $keyword = trim($this->get['keyword'] ?? '');
        $status  = $this->get['status'] ?? '';

        $query = MemberModel::order('id desc');
        if ($keyword) $query->where('nickname|email|mobile', 'like', "%{$keyword}%");
        if ($status !== '') $query->where('status', (int)$status);

        $list = $query->paginate(['list_rows' => 20, 'page' => $page]);
        return $this->view(['list' => $list, 'keyword' => $keyword, 'status' => $status]);
    }

    public function add()
    {
        if ($this->isPost()) {
            try {
                $data = $this->post;
                if (empty($data['nickname']))   throw new \Exception('请填写昵称');
                if (empty($data['password']))   throw new \Exception('请填写密码');
                if (!empty($data['email']) && MemberModel::where('email', $data['email'])->count()) {
                    throw new \Exception('该邮箱已存在');
                }
                MemberModel::create($data);
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '添加失败');
            }
            return success('添加成功', 'index');
        }
        return $this->view();
    }

    public function edit()
    {
        $member = MemberModel::find($this->get['id']);
        if (!$member) return error('用户不存在');

        if ($this->isPost()) {
            try {
                $data = $this->post;
                unset($data['id']);
                // 密码为空则不修改
                if (empty($data['password'])) unset($data['password']);
                $member->save($data);
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '修改失败');
            }
            return success('修改成功', 'index');
        }
        return $this->view(['member' => $member]);
    }

    public function del()
    {
        if (!$this->isPost()) return error('非法请求');
        try {
            $member = MemberModel::find($this->post['id']);
            if (!$member) throw new \Exception('用户不存在');
            $member->delete();
        } catch (\Exception $e) {
            return error($e->getMessage() ?: '删除失败');
        }
        return success('删除成功');
    }

    /**
     * 禁用/启用
     */
    public function status()
    {
        if (!$this->isPost()) return error('非法请求');
        $member = MemberModel::find($this->post['id']);
        if (!$member) return error('用户不存在');
        $newStatus = $member->status == 1 ? 0 : 1;
        $member->save(['status' => $newStatus]);
        return success($newStatus ? '已启用' : '已禁用');
    }

    /**
     * 调整余额
     */
    public function balance()
    {
        if (!$this->isPost()) return error('非法请求');
        try {
            $member = MemberModel::find($this->post['id']);
            if (!$member) throw new \Exception('用户不存在');
            $amount = (float)($this->post['amount'] ?? 0);
            $remark = trim($this->post['remark'] ?? '管理员调整');
            if ($amount == 0) throw new \Exception('调整金额不能为0');
            $member->changeBalance($amount, $remark);
            Event::emit('user.balance.changed', ['user_id' => $member->id, 'amount' => $amount, 'remark' => $remark]);
        } catch (\Exception $e) {
            return error($e->getMessage() ?: '操作失败');
        }
        return success('余额调整成功');
    }
}
