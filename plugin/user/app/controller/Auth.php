<?php
namespace plugin\user\app\controller;

use plugin\user\app\model\Member;
use support\Request;
use Webman\Event\Event;

class Auth
{
    /**
     * 注册
     */
    public function register(Request $request)
    {
        if (!get_config('user.register_enable', 1)) {
            return json(['code' => 0, 'msg' => '注册已关闭']);
        }

        if ($request->isPost()) {
            try {
                $data = $request->post();
                if (empty($data['nickname']))        throw new \Exception('请填写昵称');
                if (empty($data['password']))        throw new \Exception('请填写密码');
                if (strlen($data['password']) < 6)  throw new \Exception('密码不能少于6位');
                if (empty($data['email']) && empty($data['mobile'])) {
                    throw new \Exception('请填写邮箱或手机号');
                }
                if (!empty($data['email']) && Member::where('email', $data['email'])->count()) {
                    throw new \Exception('该邮箱已注册');
                }
                if (!empty($data['mobile']) && Member::where('mobile', $data['mobile'])->count()) {
                    throw new \Exception('该手机号已注册');
                }

                $member = Member::create([
                    'nickname' => $data['nickname'],
                    'email'    => $data['email']   ?? '',
                    'mobile'   => $data['mobile']  ?? '',
                    'password' => $data['password'],
                    'avatar'   => '',
                    'status'   => 1,
                ]);

                // 注册赠送余额
                $bonus = (float)get_config('user.register_bonus', 0);
                if ($bonus > 0) {
                    $member->changeBalance($bonus, '注册赠送');
                }

                Event::emit('user.registered', $member->toArray());

                // 自动登录
                $request->session()->set('user', $member->toArray());

                return json(['code' => 1, 'msg' => '注册成功']);
            } catch (\Exception $e) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
        }

        return view('auth/register');
    }

    /**
     * 登录
     */
    public function login(Request $request)
    {
        if ($request->isPost()) {
            try {
                $account  = trim($request->post('account', ''));
                $password = $request->post('password', '');
                if (!$account || !$password) throw new \Exception('请填写账号和密码');

                $member = Member::where('email', $account)
                    ->orerWhere('mobile', $account)
                    ->find();

                if (!$member)                   throw new \Exception('账号不存在');
                if (!$member->verifyPassword($password)) throw new \Exception('密码错误');
                if ($member->status != 1)      throw new \Exception('账号已被禁用');

                $request->session()->set('user', $member->toArray());
                Event::emit('user.login', $member->toArray());

                return json(['code' => 1, 'msg' => '登录成功']);
            } catch (\Exception $e) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
        }

        return view('auth/login');
    }

    /**
     * 退出
     */
    public function logout(Request $request)
    {
        $request->session()->forget('user');
        return json(['code' => 1, 'msg' => '已退出']);
    }
}
