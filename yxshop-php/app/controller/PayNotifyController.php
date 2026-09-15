<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\service\PayService;

class PayNotifyController extends BaseController
{
    protected $payService;

    public function __construct()
    {
        $this->payService = new PayService();
    }

    public function alipay(Request $request): Response
    {
        $data = $request->all();
        $result = $this->payService->handleNotify('alipay', $data);

        if ($result['success']) {
            return response('success');
        }
        return response('fail');
    }

    public function wechat(Request $request): Response
    {
        $data = $request->all();
        $result = $this->payService->handleNotify('wechat', $data);

        if ($result['success']) {
            return response('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>')
                ->header('Content-Type', 'application/xml');
        }
        return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[' . $result['message'] . ']]></return_msg></xml>')
            ->header('Content-Type', 'application/xml');
    }

    public function unionpay(Request $request): Response
    {
        $data = $request->all();
        $result = $this->payService->handleNotify('unionpay', $data);

        if ($result['success']) {
            return response('success');
        }
        return response('fail');
    }

    public function return(Request $request): Response
    {
        $method = $request->get('method', 'alipay');
        $data = $request->all();
        $result = $this->payService->handleNotify($method, $data);

        if ($result['success']) {
            return $this->success($data, '支付成功');
        }
        return $this->error($result['message'], 1, $data);
    }
}
