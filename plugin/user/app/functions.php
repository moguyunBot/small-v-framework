<?php
/**
 * 用户插件全局函数
 */

/**
 * 获取当前登录的前台用户
 * @param string|null $field 指定字段
 * @return mixed
 */
function user($field = null)
{
    $session = request()->session();
    $user = $session->get('user');
    if (!$user) return null;
    if ($field === null) return $user;
    return $user[$field] ?? null;
}

/**
 * 刷新前台用户 session
 */
function refresh_user_session(): void
{
    $user = request()->session()->get('user');
    if (!$user) return;
    $member = \plugin\user\app\model\Member::find($user['id']);
    if (!$member || $member->status != 1) {
        request()->session()->forget('user');
        return;
    }
    request()->session()->set('user', $member->toArray());
}
