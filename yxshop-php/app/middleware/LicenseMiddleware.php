<?php

declare(strict_types=1);

/**
 * YXShop 授权中间件
 *
 * 职责：
 *  - 拦截商业版/企业版路由
 *  - 检查当前授权是否有效
 *  - 检查当前版本是否有权访问对应功能模块
 *  - 无权限时返回 403 + 升级提示
 *
 * 用法（在路由配置中）：
 *   Route::group('/admin/api/dealer', function () {
 *       ...
 *   })->middleware([
 *       \app\middleware\LicenseMiddleware::class,
 *       \app\middleware\AdminAuthMiddleware::class,
 *   ]);
 *
 * @package app\middleware
 */

namespace app\middleware;

use app\common\LicenseManager;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class LicenseMiddleware implements MiddlewareInterface
{
    /**
     * 中间件处理
     */
    public function process(Request $request, callable $handler): Response
    {
        $license = LicenseManager::getInstance();

        // 开源版且非试用模式，检查路由是否需要更高版本
        if (!$license->isValid()) {
            return $this->unauthorizedResponse($license);
        }

        // 检查路由对应的功能模块
        $path = $request->path();
        $module = $license->getRequiredModuleByRoute($path);

        if ($module !== null && !$license->hasModule($module)) {
            return $this->forbiddenResponse($license, $module);
        }

        return $handler($request);
    }

    /**
     * 授权无效响应（403）
     */
    private function unauthorizedResponse(LicenseManager $license): Response
    {
        $editionName = $license->getEditionName();

        return json([
            'code' => 403,
            'message' => "授权已过期或无效，请续费{$editionName}。",
            'data' => [
                'edition' => $license->getEdition(),
                'edition_name' => $editionName,
                'expiry' => $license->getExpiry(),
                'upgrade_url' => 'https://www.yxshop.com/pricing',
            ],
        ], 403);
    }

    /**
     * 功能模块无权限响应（403）
     */
    private function forbiddenResponse(LicenseManager $license, string $module): Response
    {
        $features = config('license.features', []);
        $moduleName = $features[$module]['name'] ?? $module;
        $minEdition = $features[$module]['min_edition'] ?? 'commercial';

        $editionNames = [
            'commercial' => '商业版',
            'enterprise' => '企业版',
            'saas' => 'SaaS 版',
        ];

        $needEdition = $editionNames[$minEdition] ?? $minEdition;

        return json([
            'code' => 403,
            'message' => "此功能需要{$needEdition}授权。",
            'data' => [
                'module' => $module,
                'module_name' => $moduleName,
                'current_edition' => $license->getEdition(),
                'required_edition' => $minEdition,
                'upgrade_url' => 'https://www.yxshop.com/pricing',
            ],
        ], 403);
    }
}
