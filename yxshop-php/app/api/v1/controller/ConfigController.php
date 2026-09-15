<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\SettingService;

/**
 * 公开配置接口
 *
 * 改用 SettingService 取 system / theme / payment / app 分组的设置。
 * 不要求登录（app_id 来自 query 参数，默认 0=全局）。
 */
class ConfigController extends BaseController
{
    /** @var SettingService */
    protected $settingService;

    public function __construct()
    {
        $this->settingService = new SettingService();
    }

    /**
     * 系统配置
     */
    public function getSystemConfig(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        $data = $this->settingService->getGroupSettings('system', $appId);
        return $this->success($data);
    }

    /**
     * 主题配置
     */
    public function getThemeConfig(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        $data = $this->settingService->getGroupSettings('theme', $appId);
        return $this->success($data);
    }

    /**
     * 支付配置（仅返回前端能用的公开字段，敏感信息不返回）
     */
    public function getPaymentConfig(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        $data = $this->settingService->getGroupSettings('payment', $appId);
        // 过滤敏感字段（avoid 泄漏 api_key/secret/private_key 之类）
        $public = [];
        foreach ((array) $data as $key => $value) {
            $lower = strtolower((string) $key);
            if (preg_match('/(key|secret|password|cert|private)/', $lower)) {
                continue;
            }
            $public[$key] = $value;
        }
        return $this->success($public);
    }

    /**
     * 应用配置
     */
    public function getAppConfig(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        $data = $this->settingService->getGroupSettings('app', $appId);
        return $this->success($data);
    }

    public function getSearchHotWords(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        $data = $this->settingService->getGroupSettings('search', $appId);
        $words = isset($data['hot_words']) ? $data['hot_words'] : [];
        if (is_string($words)) {
            $words = array_filter(array_map('trim', explode(',', $words)));
        }
        return $this->success(['words' => $words]);
    }
}
