<?php

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class VersionCheckMiddleware implements MiddlewareInterface
{
    /**
     * 功能版本映射
     */
    private $featureVersions = [
        // 开源版本功能
        'basic' => [
            'user_management' => true,
            'product_management' => true,
            'order_management' => true,
            'inventory_management' => true,
            'payment_management' => true,
            'basic_coupon' => true,
            'basic_discount' => true,
            'basic_promotion' => true,
            'miniprogram_basic' => true,
            'h5_basic' => true,
        ],
        
        // 进阶版本功能
        'professional' => [
            'advanced_coupon' => false,
            'advanced_discount' => false,
            'promotion_management' => false,
            'points_system' => false,
            'order_batch' => false,
            'inventory_warning' => false,
            'user_analytics' => false,
            'notification_system' => false,
            'basic_analytics' => false,
        ],
        
        // 高级版本功能
        'enterprise' => [
            'distribution_system' => false,
            'multi_warehouse' => false,
            'advanced_logistics' => false,
            'after_sales_management' => false,
            'review_management' => false,
            'miniprogram_management' => false,
            'system_management' => false,
            'advanced_analytics' => false,
            'multi_tenant' => false,
            'multi_language' => false,
            'api_management' => false,
        ]
    ];

    /**
     * 处理请求
     */
    public function process(Request $request, callable $handler): Response
    {
        $feature = $request->route('feature');
        $version = $this->getCurrentVersion($request);
        
        if ($feature && !$this->hasFeature($version, $feature)) {
            return $this->upgradeRequired($feature, $version);
        }
        
        return $handler($request);
    }

    /**
     * 获取当前版本
     */
    private function getCurrentVersion(Request $request): string
    {
        // 从请求头或用户信息中获取版本
        $version = $request->header('X-Version', 'basic');
        
        // 如果是登录用户，从用户信息中获取版本
        if ($request->user()) {
            $version = $request->user()->version ?? 'basic';
        }
        
        return $version;
    }

    /**
     * 检查是否有功能权限
     */
    private function hasFeature(string $version, string $feature): bool
    {
        // 检查基础版本功能
        if (isset($this->featureVersions['basic'][$feature])) {
            return $this->featureVersions['basic'][$feature];
        }
        
        // 检查进阶版本功能
        if (isset($this->featureVersions['professional'][$feature])) {
            if ($version === 'professional' || $version === 'enterprise') {
                return $this->featureVersions['professional'][$feature];
            }
            return false;
        }
        
        // 检查高级版本功能
        if (isset($this->featureVersions['enterprise'][$feature])) {
            if ($version === 'enterprise') {
                return $this->featureVersions['enterprise'][$feature];
            }
            return false;
        }
        
        return false;
    }

    /**
     * 升级提示
     */
    private function upgradeRequired(string $feature, string $currentVersion): Response
    {
        $requiredVersion = $this->getRequiredVersion($feature);
        
        return json([
            'code' => 403,
            'msg' => '功能需要升级到 ' . $requiredVersion . ' 版本',
            'data' => [
                'feature' => $feature,
                'current_version' => $currentVersion,
                'required_version' => $requiredVersion,
                'upgrade_url' => $this->getUpgradeUrl($requiredVersion)
            ]
        ]);
    }

    /**
     * 获取功能所需版本
     */
    private function getRequiredVersion(string $feature): string
    {
        if (isset($this->featureVersions['professional'][$feature])) {
            return 'Professional Edition';
        }
        
        if (isset($this->featureVersions['enterprise'][$feature])) {
            return 'Enterprise Edition';
        }
        
        return 'Community Edition';
    }

    /**
     * 获取升级链接
     */
    private function getUpgradeUrl(string $version): string
    {
        $urls = [
            'Professional Edition' => '/upgrade/professional',
            'Enterprise Edition' => '/upgrade/enterprise'
        ];
        
        return $urls[$version] ?? '/upgrade';
    }
}
