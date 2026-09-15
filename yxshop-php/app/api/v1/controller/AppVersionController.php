<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\AppVersionService;

class AppVersionController extends BaseController
{
    protected $versionService;
    
    public function __construct()
    {
        $this->versionService = new AppVersionService();
    }
    
    /**
     * 获取最新版本
     */
    public function getLatest(Request $request)
    {
        $platform = $request->get('platform', 'android');
        $channel = $request->get('channel', 'official');
        
        // 验证平台类型
        $validPlatforms = ['android', 'ios', 'web', 'miniprogram'];
        if (!in_array($platform, $validPlatforms)) {
            return $this->error('无效的平台类型');
        }
        
        $result = $this->versionService->getLatestVersion($platform, $channel);
        
        if ($result['success']) {
            return $this->success($result['version']);
        } else {
            return $this->error($result['message']);
        }
    }
    
    /**
     * 检查是否需要更新
     */
    public function checkUpdate(Request $request)
    {
        $platform = $request->get('platform', 'android');
        $currentVersionCode = (int)$request->get('current_version_code');
        $channel = $request->get('channel', 'official');
        
        // 参数验证
        if (!$currentVersionCode) {
            return $this->error('当前版本代码不能为空');
        }
        
        $result = $this->versionService->checkUpdate($platform, $currentVersionCode, $channel);
        
        if ($result['success']) {
            return $this->success($result);
        } else {
            return $this->error($result['message']);
        }
    }
    
    /**
     * 获取强制更新版本
     */
    public function getForceUpdate(Request $request)
    {
        $platform = $request->get('platform', 'android');
        $channel = $request->get('channel', 'official');
        
        $result = $this->versionService->getForceUpdateVersion($platform, $channel);
        
        if ($result['success']) {
            return $this->success($result['version']);
        } else {
            return $this->error($result['message']);
        }
    }
    
    /**
     * 获取版本信息
     */
    public function getInfo(Request $request)
    {
        $platform = $request->get('platform', 'android');
        $versionCode = (int)$request->get('version_code');
        
        // 参数验证
        if (!$versionCode) {
            return $this->error('版本代码不能为空');
        }
        
        $result = $this->versionService->getVersionByCode($platform, $versionCode);
        
        if ($result['success']) {
            return $this->success($result['version']);
        } else {
            return $this->error($result['message']);
        }
    }
} 