<?php

namespace app\controller;

use app\service\VersionService;
use support\Request;
use support\Response;
use app\exception\BusinessException;

class VersionController extends BaseController
{
    private $versionService;

    public function __construct()
    {
        $this->versionService = new VersionService();
    }

    public function getVersion(Request $request): Response
    {
        $version = $request->get('version', 'basic');
        $info = $this->versionService->getVersionInfo($version);

        return $this->success($info);
    }

    public function getAllVersions(Request $request): Response
    {
        $versions = $this->versionService->getAllVersions();

        return $this->success($versions);
    }

    public function checkFeature(Request $request): Response
    {
        $feature = $request->get('feature');
        $userId = $request->userId ?? 0;

        if (!$feature) {
            throw new BusinessException('功能参数不能为空');
        }

        $hasFeature = $this->versionService->hasFeature($userId, $feature);

        return $this->success([
            'feature' => $feature,
            'has_feature' => $hasFeature,
            'user_version' => $this->versionService->getUserVersion($userId),
        ]);
    }

    public function checkUpgrade(Request $request): Response
    {
        $currentVersion = $request->get('current_version', 'basic');
        $targetVersion = $request->get('target_version');

        if (!$targetVersion) {
            throw new BusinessException('目标版本不能为空');
        }

        $upgradeInfo = $this->versionService->checkUpgrade($currentVersion, $targetVersion);

        return $this->success($upgradeInfo);
    }

    public function upgrade(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        $newVersion = $request->post('version');

        if (!$newVersion) {
            throw new BusinessException('版本参数不能为空');
        }

        $currentVersion = $this->versionService->getUserVersion($userId);
        $upgradeInfo = $this->versionService->checkUpgrade($currentVersion, $newVersion);

        if (!$upgradeInfo['can_upgrade']) {
            throw new BusinessException('无法升级到该版本');
        }

        $success = $this->versionService->upgradeUser($userId, $newVersion);

        if (!$success) {
            throw new BusinessException('升级失败');
        }

        return $this->success([
            'old_version' => $currentVersion,
            'new_version' => $newVersion,
            'new_features' => $upgradeInfo['new_features'] ?? [],
        ], '升级成功');
    }

    public function getComparison(Request $request): Response
    {
        $comparison = $this->versionService->getVersionComparison();

        return $this->success($comparison);
    }

    public function getFeatureUsage(Request $request): Response
    {
        $feature = $request->get('feature');

        if (!$feature) {
            throw new BusinessException('功能参数不能为空');
        }

        $usage = $this->versionService->getFeatureUsage($feature);

        return $this->success($usage);
    }
}
