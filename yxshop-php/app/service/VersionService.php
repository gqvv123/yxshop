<?php

namespace app\service;

use app\repository\UserRepository;
use support\Redis;

/**
 * 版本管理服务
 *
 * @property UserRepository $repository
 */
class VersionService
{
    protected $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    /**
     * 版本配置
     */
    private $versions = [
        'basic' => [
            'name' => 'Community Edition',
            'price' => 0,
            'features' => [
                'user_management',
                'product_management',
                'order_management',
                'inventory_management',
                'payment_management',
                'basic_coupon',
                'basic_discount',
                'basic_promotion',
                'miniprogram_basic',
                'h5_basic',
            ]
        ],
        'professional' => [
            'name' => 'Professional Edition',
            'price' => 2999,
            'features' => [
                'advanced_coupon',
                'advanced_discount',
                'promotion_management',
                'points_system',
                'order_batch',
                'inventory_warning',
                'user_analytics',
                'notification_system',
                'basic_analytics',
            ]
        ],
        'enterprise' => [
            'name' => 'Enterprise Edition',
            'price' => 9999,
            'features' => [
                'distribution_system',
                'multi_warehouse',
                'advanced_logistics',
                'after_sales_management',
                'review_management',
                'miniprogram_management',
                'system_management',
                'advanced_analytics',
                'multi_tenant',
                'multi_language',
                'api_management',
            ]
        ]
    ];

    /**
     * 获取用户版本
     */
    public function getUserVersion(int $userId): string
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return 'basic';
        }
        
        return $user->version ?? 'basic';
    }

    /**
     * 检查用户是否有功能权限
     */
    public function hasFeature(int $userId, string $feature): bool
    {
        $version = $this->getUserVersion($userId);
        return $this->checkFeature($version, $feature);
    }

    /**
     * 检查版本是否有功能
     */
    public function checkFeature(string $version, string $feature): bool
    {
        // 基础功能所有版本都有
        if (in_array($feature, $this->versions['basic']['features'])) {
            return true;
        }
        
        // 进阶功能
        if (in_array($feature, $this->versions['professional']['features'])) {
            return in_array($version, ['professional', 'enterprise']);
        }
        
        // 高级功能
        if (in_array($feature, $this->versions['enterprise']['features'])) {
            return $version === 'enterprise';
        }
        
        return false;
    }

    /**
     * 获取版本信息
     */
    public function getVersionInfo(string $version): array
    {
        return $this->versions[$version] ?? $this->versions['basic'];
    }

    /**
     * 获取所有版本信息
     */
    public function getAllVersions(): array
    {
        return $this->versions;
    }

    /**
     * 升级用户版本
     */
    public function upgradeUser(int $userId, string $newVersion): bool
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return false;
        }
        
        $user->version = $newVersion;
        $user->upgraded_at = date('Y-m-d H:i:s');
        $user->save();
        
        // 记录升级日志
        $this->logUpgrade($userId, $user->version, $newVersion);
        
        return true;
    }

    /**
     * 检查版本升级
     */
    public function checkUpgrade(string $currentVersion, string $targetVersion): array
    {
        $current = $this->getVersionInfo($currentVersion);
        $target = $this->getVersionInfo($targetVersion);
        
        return [
            'can_upgrade' => $this->canUpgrade($currentVersion, $targetVersion),
            'current_version' => $current,
            'target_version' => $target,
            'price' => $target['price'] - $current['price'],
            'new_features' => $this->getNewFeatures($currentVersion, $targetVersion)
        ];
    }

    /**
     * 检查是否可以升级
     */
    private function canUpgrade(string $currentVersion, string $targetVersion): bool
    {
        $levels = ['basic', 'professional', 'enterprise'];
        $currentLevel = array_search($currentVersion, $levels);
        $targetLevel = array_search($targetVersion, $levels);
        
        return $targetLevel > $currentLevel;
    }

    /**
     * 获取新功能
     */
    private function getNewFeatures(string $currentVersion, string $targetVersion): array
    {
        $current = $this->getVersionInfo($currentVersion);
        $target = $this->getVersionInfo($targetVersion);
        
        $currentFeatures = $current['features'];
        $targetFeatures = $target['features'];
        
        return array_diff($targetFeatures, $currentFeatures);
    }

    /**
     * 记录升级日志
     */
    private function logUpgrade(int $userId, string $oldVersion, string $newVersion): void
    {
        $log = [
            'user_id' => $userId,
            'old_version' => $oldVersion,
            'new_version' => $newVersion,
            'upgraded_at' => date('Y-m-d H:i:s'),
            'ip' => request()->getRealIp()
        ];
        
        Redis::lpush('upgrade_logs', json_encode($log));
    }

    /**
     * 获取功能使用统计
     */
    public function getFeatureUsage(string $feature): array
    {
        $cacheKey = "feature_usage:{$feature}";
        $usage = Redis::get($cacheKey);
        
        if (!$usage) {
            // 从数据库统计功能使用情况
            $usage = $this->calculateFeatureUsage($feature);
            Redis::setex($cacheKey, 3600, json_encode($usage));
        }
        
        return json_decode($usage, true);
    }

    /**
     * 计算功能使用情况
     */
    private function calculateFeatureUsage(string $feature): array
    {
        // 这里可以根据具体功能统计使用情况
        return [
            'total_users' => 0,
            'active_users' => 0,
            'usage_rate' => 0,
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * 获取版本对比
     */
    public function getVersionComparison(): array
    {
        $comparison = [];
        
        foreach ($this->versions as $key => $version) {
            $comparison[$key] = [
                'name' => $version['name'],
                'price' => $version['price'],
                'feature_count' => count($version['features']),
                'features' => $version['features']
            ];
        }
        
        return $comparison;
    }
}
