<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class ApiVersionMiddleware implements MiddlewareInterface
{
    /**
     * 支持的API版本
     */
    protected $supportedVersions = ['v1', 'v2'];
    
    /**
     * 默认版本
     */
    protected $defaultVersion = 'v1';
    
    /**
     * 版本弃用信息
     */
    protected $deprecatedVersions = [
        // 'v1' => '2024-12-31' // 示例：V1版本将在2024年底弃用
    ];
    
    /**
     * 版本特性映射
     */
    protected $versionFeatures = [
        'v1' => [
            'status' => 'stable',
            'features' => ['基础功能', '用户管理', '订单管理'],
            'breaking_changes' => []
        ],
        'v2' => [
            'status' => 'beta',
            'features' => ['基础功能', '用户管理', '订单管理', '高级功能', 'AI推荐'],
            'breaking_changes' => [
                'user.info' => '新增preferences字段',
                'order.create' => '新增delivery_options参数'
            ]
        ]
    ];

    public function process(Request $request, callable $handler): Response
    {
        // 获取请求的API版本
        $version = $this->getApiVersion($request);
        
        // 检查版本是否支持
        if (!in_array($version, $this->supportedVersions)) {
            return $this->unsupportedVersionResponse($version);
        }
        
        // 检查版本是否已弃用
        if ($this->isVersionDeprecated($version)) {
            return $this->deprecatedVersionResponse($version);
        }
        
        // 设置版本信息到请求中
        $request->apiVersion = $version;
        $request->apiFeatures = $this->versionFeatures[$version] ?? [];
        
        // 添加版本信息到响应头
        $response = $handler($request);
        $response->header('X-API-Version', $version);
        $response->header('X-API-Status', $this->versionFeatures[$version]['status'] ?? 'unknown');
        
        // 如果是弃用版本，添加警告头
        if ($this->isVersionDeprecated($version)) {
            $response->header('X-API-Deprecation-Warning', 'true');
        }
        
        return $response;
    }
    
    /**
     * 获取API版本
     */
    protected function getApiVersion(Request $request): string
    {
        // 从URL路径获取版本
        $path = $request->path();
        if (preg_match('/^\/api\/(v\d+)/', $path, $matches)) {
            return $matches[1];
        }
        
        // 从请求头获取版本
        $version = $request->header('X-API-Version');
        if (in_array($version, $this->supportedVersions)) {
            return $version;
        }
        
        // 从查询参数获取版本
        $version = $request->get('version');
        if (in_array($version, $this->supportedVersions)) {
            return $version;
        }
        
        // 返回默认版本
        return $this->defaultVersion;
    }
    
    /**
     * 检查版本是否已弃用
     */
    protected function isVersionDeprecated(string $version): bool
    {
        if (!isset($this->deprecatedVersions[$version])) {
            return false;
        }
        
        $deprecationDate = $this->deprecatedVersions[$version];
        if (is_string($deprecationDate)) {
            return strtotime($deprecationDate) <= time();
        }
        
        return $deprecationDate;
    }
    
    /**
     * 不支持的版本响应
     */
    protected function unsupportedVersionResponse(string $version): Response
    {
        return response()->json([
            'error' => 'Unsupported API Version',
            'message' => "API版本 '{$version}' 不支持",
            'supported_versions' => $this->supportedVersions,
            'default_version' => $this->defaultVersion,
            'documentation' => '/api/docs'
        ], 400);
    }
    
    /**
     * 已弃用版本响应
     */
    protected function deprecatedVersionResponse(string $version): Response
    {
        $deprecationDate = $this->deprecatedVersions[$version];
        $sunsetDate = is_string($deprecationDate) ? $deprecationDate : 'unknown';
        
        return response()->json([
            'error' => 'Deprecated API Version',
            'message' => "API版本 '{$version}' 已弃用",
            'deprecation_date' => $deprecationDate,
            'sunset_date' => $sunsetDate,
            'recommended_version' => $this->getRecommendedVersion(),
            'migration_guide' => "/api/docs/migration/{$version}",
            'support_contact' => 'api-support@example.com'
        ], 410); // 410 Gone
    }
    
    /**
     * 获取推荐版本
     */
    protected function getRecommendedVersion(): string
    {
        // 优先推荐稳定版本
        foreach ($this->versionFeatures as $version => $info) {
            if ($info['status'] === 'stable') {
                return $version;
            }
        }
        
        // 如果没有稳定版本，返回最新版本
        return end($this->supportedVersions);
    }
} 