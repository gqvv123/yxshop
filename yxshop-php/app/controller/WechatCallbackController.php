<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\service\WechatAuthService;

class WechatCallbackController
{
    protected $wechatAuthService;

    public function __construct()
    {
        $this->wechatAuthService = new WechatAuthService();
    }

    public function userCallback(Request $request): Response
    {
        $code = $request->get('code', '');
        $state = $request->get('state', '');

        if (empty($code) || empty($state)) {
            return new Response(200, [], '<html><body><h3>授权失败：参数缺失</h3><script>window.close()</script></body></html>');
        }

        $this->wechatAuthService->handleOAuthCallback($code, $state, 'user_login');

        return new Response(200, [], '<html><body><h3>授权成功，请返回原页面</h3><script>window.close()</script></body></html>');
    }

    public function adminCallback(Request $request): Response
    {
        $code = $request->get('code', '');
        $state = $request->get('state', '');

        if (empty($code) || empty($state)) {
            return new Response(200, [], '<html><body><h3>授权失败：参数缺失</h3><script>window.close()</script></body></html>');
        }

        $this->wechatAuthService->handleOAuthCallback($code, $state, 'admin_login');

        return new Response(200, [], '<html><body><h3>授权成功，请返回原页面</h3><script>window.close()</script></body></html>');
    }
}