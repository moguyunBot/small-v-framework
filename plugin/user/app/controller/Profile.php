<?php
namespace plugin\user\app\controller;

use plugin\user\app\model\Member;
use support\Request;

class Profile
{
    /**
     * 个人中心
     */
    public function index(Request $request)
    {
        $user = user();
        if (!$user) return redirect('/app/user/auth/login');
        refresh_user_session();
        $member = Member::find($user['id']);
        return view('profile/index', ['member' => $member]);
    }

    /**
     * 修改资料
     */
    public function edit(Request $request)
    {
        $user = user();
        if (!$user) return json(['code' => 0, 'msg' => '请先登录']);

        if ($request->isPost()) {
            try {
                $data = $request->post();
                $allow = ['nickname', 'avatar'];
                $save  = array_intersect_key($data, array_flip($allow));
                if (!empty($data['password'])) {
                    if (strlen($data['password']) < 6) throw new \Exception('密码不能少于6位');
                    $save['password'] = $data['password'];
                }
                Member::where('id', $user['id'])->save($save);
                refresh_user_session();
                return json(['code' => 1, 'msg' => '保存成功']);
            } catch (\Exception $e) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
        }

        return view('profile/edit', ['member' => Member::find($user['id'])]);
    }
}
