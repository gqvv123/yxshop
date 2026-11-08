<?php

namespace app\service;

/**
 * APP 客户端版本管理服务（最小可用桩）
 *
 * 后续接入实际 yxshop_app_versions 表后改造，当前用配置/默认值兜底，
 * 保证前端不因接口缺失而崩溃。
 */
class AppVersionService
{
    /**
     * 默认平台版本（按需在 config/app.php 配置覆盖）
     */
    private function defaultVersion(string $platform): array
    {
        $cfg = (array) config('app.versions.' . $platform, []);
        return array_merge([
            'platform'     => $platform,
            'version_code' => 1,
            'version_name' => '1.0.0',
            'channel'      => 'official',
            'download_url' => '',
            'release_note' => '',
            'force_update' => false,
            'released_at'  => time(),
        ], $cfg);
    }

    public function getLatestVersion(string $platform, string $channel = 'official'): array
    {
        return ['success' => true, 'version' => $this->defaultVersion($platform), 'message' => 'ok'];
    }

    public function checkUpdate(string $platform, int $currentVersionCode, string $channel = 'official'): array
    {
        $latest = $this->defaultVersion($platform);
        $hasUpdate = $currentVersionCode < (int) $latest['version_code'];
        return [
            'success'     => true,
            'has_update'  => $hasUpdate,
            'force'       => $hasUpdate && !empty($latest['force_update']),
            'version'     => $hasUpdate ? $latest : null,
            'message'     => 'ok',
        ];
    }

    public function getForceUpdateVersion(string $platform, string $channel = 'official'): array
    {
        $v = $this->defaultVersion($platform);
        if (!empty($v['force_update'])) {
            return ['success' => true, 'version' => $v, 'message' => 'ok'];
        }
        return ['success' => true, 'version' => null, 'message' => 'no force update'];
    }

    public function getVersionByCode(string $platform, int $versionCode): array
    {
        return ['success' => true, 'version' => $this->defaultVersion($platform), 'message' => 'ok'];
    }
}
