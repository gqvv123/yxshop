<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\UserAuthService;

class AuthController extends BaseController
{
    /** @var UserAuthService */
    protected $authService;

    public function __construct()
    {
        $this->authService = new UserAuthService();
    }

    /**
     * 用户登录
     */
    public function login(Request $request)
    {
        $data = $request->post();

        $identifier = (string) ($data['username'] ?? $data['account'] ?? $data['phone'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $appId = (int) ($data['app_id'] ?? 0);

        if ($identifier === '') {
            return $this->error('账号不能为空');
        }
        if ($password === '') {
            return $this->error('密码不能为空');
        }

        $result = $this->authService->login($identifier, $password, $appId, $request->getRealIp());

        return $result['success']
            ? $this->success($result['data'], $result['message'])
            : $this->error($result['message']);
    }

    /**
     * 用户注册
     */
    public function register(Request $request)
    {
        $data = $request->post();

        if (empty($data['username']) && empty($data['phone'])) {
            return $this->error('账号或手机号不能为空');
        }
        if (empty($data['password'])) {
            return $this->error('密码不能为空');
        }
        // 用 username 兜底
        $data['username'] = $data['username'] ?? $data['phone'];

        $result = $this->authService->register($data);

        return $result['success']
            ? $this->success($result['data'], $result['message'])
            : $this->error($result['message']);
    }

    /**
     * 用户登出
     */
    public function logout(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        $authHeader = $request->header('Authorization', '');
        $token = '';
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $m)) {
            $token = $m[1];
        }

        // 优先按 token 加黑名单，兜底按 userId 撤销 refresh token
        $result = $this->authService->logout($token !== '' ? $token : $userId);

        return $result['success']
            ? $this->success(null, $result['message'])
            : $this->error($result['message']);
    }

    /**
     * 刷新Token
     */
    public function refreshToken(Request $request)
    {
        $refreshToken = (string) $request->post('refresh_token', '');

        if ($refreshToken === '') {
            return $this->error('刷新Token不能为空');
        }

        $result = $this->authService->refreshToken($refreshToken);

        return $result['success']
            ? $this->success($result['data'], $result['message'])
            : $this->error($result['message']);
    }

    /**
     * 忘记密码
     */
    public function forgotPassword(Request $request)
    {
        $emailOrPhone = (string) ($request->post('email') ?: $request->post('phone'));
        $appId = (int) $request->post('app_id', 0);

        if ($emailOrPhone === '') {
            return $this->error('请填写邮箱或手机号');
        }

        $result = $this->authService->forgotPassword($emailOrPhone, $appId);

        return $result['success']
            ? $this->success($result['data'], $result['message'])
            : $this->error($result['message']);
    }

    /**
     * 重置密码
     */
    public function resetPassword(Request $request)
    {
        $token = (string) $request->post('token', '');
        $password = (string) $request->post('password', '');

        if ($token === '') {
            return $this->error('重置Token不能为空');
        }
        if ($password === '') {
            return $this->error('新密码不能为空');
        }

        $result = $this->authService->resetPassword($token, $password);

        return $result['success']
            ? $this->success(null, $result['message'])
            : $this->error($result['message']);
    }

    /**
     * 微信小程序一键登录
     *
     * 入参（POST）：
     *   - code           wx.login() 的临时凭证（必填）
     *   - app_id         可选，多租户业务 app_id
     *   - profile        可选，{ nickname, avatar_url, gender }
     *                    （配合 wx.getUserProfile / chooseAvatar 使用）
     */
    public function wxLogin(Request $request)
    {
        $payload = $request->post();
        if (empty($payload['code'])) {
            return $this->error('缺少 wx.login code');
        }
        $payload['ip'] = $request->getRealIp();

        $result = $this->authService->wxLogin($payload);
        return $result['success']
            ? $this->success($result['data'], $result['message'])
            : $this->error($result['message']);
    }

    /**
     * 同步微信前端获取的头像/昵称
     */
    public function updateWxProfile(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $profile = (array) $request->post('profile', $request->post());
        $result = $this->authService->updateWxProfile((int) $userId, $profile);
        return $result['success']
            ? $this->success($result['data'], $result['message'])
            : $this->error($result['message']);
    }

    /**
     * 微信手机号一键绑定（需要登录）
     *
     * 入参（POST），二选一：
     *   A) code            getPhoneNumber 返回的动态令牌（推荐）
     *   B) encrypted_data + iv   旧版加密数据（用登录缓存的 session_key 解密）
     */
    public function bindPhone(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $payload = $request->post();
        if (empty($payload['code']) && (empty($payload['encrypted_data']) || empty($payload['iv']))) {
            return $this->error('缺少手机号授权参数');
        }
        $result = $this->authService->bindWxPhone((int) $userId, $payload);
        return $result['success']
            ? $this->success($result['data'], $result['message'])
            : $this->error($result['message']);
    }
}
