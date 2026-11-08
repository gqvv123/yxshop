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
     * 基础配置（站点名称 / 标题 / 关键词 / 描述 / LOGO / 客服电话邮箱 / 版权）
     *
     * 注意：这些键在 yxshop_settings 里的 group 是 `basic`，不是 `system`。
     * 此前只有 getSystemConfig（查 group=system）时返回一直是空数组，
     * 前端因此拿不到站点名，只能硬编码。
     */
    public function getBasicConfig(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        $data = $this->settingService->getGroupSettings('basic', $appId);
        return $this->success($data);
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
