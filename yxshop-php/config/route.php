<?php

use Webman\Route;
use support\Request;
use support\Response;

Route::disableDefaultRoute();

// 首页路由
Route::get('/', function (Request $request) {
    return redirect('/home');
});

// 主页路由
Route::get('/home', [app\controller\IndexController::class, 'index'])->name('home');

// 货币切换路由
Route::get('/currency/change/{code}', [app\controller\IndexController::class, 'changeCurrency'])->name('currency.change');

// 语言切换路由
Route::get('/language/change/{code}', [app\controller\IndexController::class, 'changeLanguage'])->name('language.change');

// 版本管理路由(公开读取)
Route::group('/version', function () {
    Route::get('/info', [app\controller\VersionController::class, 'getVersion']);
    Route::get('/all', [app\controller\VersionController::class, 'getAllVersions']);
    Route::get('/feature', [app\controller\VersionController::class, 'checkFeature']);
    Route::get('/upgrade/check', [app\controller\VersionController::class, 'checkUpgrade']);
    Route::get('/comparison', [app\controller\VersionController::class, 'getComparison']);
    Route::get('/feature/usage', [app\controller\VersionController::class, 'getFeatureUsage']);
});

// 版本管理路由(需要认证-写操作)
Route::group('/version', function () {
    Route::post('/upgrade', [app\controller\VersionController::class, 'upgrade']);
})->middleware([app\middleware\ApiAuthMiddleware::class]);

// 用户地址路由(需要认证)
Route::group('/user/address', function () {
    Route::get('/list', [app\controller\UserAddressController::class, 'list']);
    Route::get('/default', [app\controller\UserAddressController::class, 'getDefault']);
    Route::post('/store', [app\controller\UserAddressController::class, 'store']);
    Route::put('/{id}', [app\controller\UserAddressController::class, 'update']);
    Route::delete('/{id}', [app\controller\UserAddressController::class, 'destroy']);
    Route::post('/default', [app\controller\UserAddressController::class, 'setDefault']);
})->middleware([app\middleware\ApiAuthMiddleware::class]);

// 主题路由(公开读取)
Route::group('/theme', function () {
    Route::get('/list', [app\controller\ThemeController::class, 'list']);
    Route::get('/current', [app\controller\ThemeController::class, 'current']);
});

// 主题路由(需要认证-写操作)
Route::group('/theme', function () {
    Route::post('/apply', [app\controller\ThemeController::class, 'apply']);
    Route::post('/custom', [app\controller\ThemeController::class, 'custom']);
    Route::delete('/{id}', [app\controller\ThemeController::class, 'delete']);
})->middleware([app\middleware\ApiAuthMiddleware::class]);

// 推荐路由(公开读取)
Route::group('/recommendation', function () {
    Route::get('/sales-trend', [app\controller\RecommendationController::class, 'salesTrend']);
    Route::get('/hot', [app\controller\RecommendationController::class, 'hotDishes']);
    Route::get('/insights', [app\controller\RecommendationController::class, 'customerInsights']);
    Route::get('/strategies', [app\controller\RecommendationController::class, 'strategies']);
});

// 推荐路由(需要认证-写操作)
Route::group('/recommendation', function () {
    Route::post('/strategy/update', [app\controller\RecommendationController::class, 'updateStrategy']);
})->middleware([app\middleware\ApiAuthMiddleware::class]);

// 支付回调路由(无需认证)
Route::group('/pay/notify', function () {
    Route::post('/alipay', [app\controller\PayNotifyController::class, 'alipay']);
    Route::post('/wechat', [app\controller\PayNotifyController::class, 'wechat']);
    Route::post('/unionpay', [app\controller\PayNotifyController::class, 'unionpay']);
    Route::get('/return', [app\controller\PayNotifyController::class, 'return']);
});

// 微信支付回调路由(无需认证)
Route::post('/wechat/notify/pay', [app\controller\WechatNotifyController::class, 'payNotify']);
Route::post('/wechat/notify/pay-v3', [app\controller\WechatNotifyController::class, 'payNotifyV3']);
Route::post('/wechat/notify/refund', [app\controller\WechatNotifyController::class, 'refundNotify']);

// 售后路由(需要认证)
Route::group('/after-sales', function () {
    Route::get('/list', [app\controller\AfterSalesController::class, 'list']);
    Route::get('/statistics', [app\controller\AfterSalesController::class, 'statistics']);
    Route::get('/{id}', [app\controller\AfterSalesController::class, 'show']);
    Route::post('/{id}/process', [app\controller\AfterSalesController::class, 'process']);
    Route::post('/{id}/reject', [app\controller\AfterSalesController::class, 'reject']);
})->middleware([app\middleware\ApiAuthMiddleware::class]);

// API路由已配置在 config/route/api.php
// 整合平台管理端、商家管理端、API接口端、后台管理员端

// 加载各端路由配置
require_once __DIR__ . '/route/api.php';
require_once __DIR__ . '/route/admin.php';
require_once __DIR__ . '/route/admin_api.php';

// 管理后台 SPA 入口（参考 yxadmin 项目模式）
Route::get('/yxadmin', function () {
    $indexPath = public_path() . '/yxadmin/index.html';
    if (file_exists($indexPath)) {
        return response(file_get_contents($indexPath), 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
    return response('Not Found', 404);
});
Route::get('/yxadmin/', function () {
    $indexPath = public_path() . '/yxadmin/index.html';
    if (file_exists($indexPath)) {
        return response(file_get_contents($indexPath), 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
    return response('Not Found', 404);
});

// 健康检查接口
Route::get('/health', function () {
    return json([
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0'
    ]);
});

// SPA回退 + 404处理（完全参照 yxadmin 项目）
Route::fallback(function (Request $request) {
    $path = $request->path();

    if (strpos($path, '..') !== false || strpos($path, '//') !== false || strpos($path, "\0") !== false) {
        return response('<h1>403 forbidden</h1>', 403);
    }

    // SPA fallback: /yxadmin/ 下的非 API、非静态资源请求返回 index.html
    if (strpos($path, '/yxadmin') === 0 && $request->method() === 'GET') {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if (!$ext || !in_array($ext, ['js', 'css', 'map', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg', 'woff', 'woff2', 'ttf', 'eot', 'php', 'json', 'xml'])) {
            $indexPath = public_path() . '/yxadmin/index.html';
            if (file_exists($indexPath)) {
                return response(file_get_contents($indexPath), 200, ['Content-Type' => 'text/html; charset=utf-8']);
            }
        }
    }

    // API 请求返回 JSON 404
    if (strpos($path, '/admin/api') === 0 || strpos($path, '/api') === 0) {
        return json([
            'code' => 404,
            'message' => '接口不存在',
            'timestamp' => date('Y-m-d H:i:s')
        ], 404);
    }

    if ($request->method() !== 'GET') {
        return response('404 Not Found', 404);
    }

    // 前端首页 fallback
    $indexHtmlPath = public_path() . '/index.html';
    if (file_exists($indexHtmlPath)) {
        return response(file_get_contents($indexHtmlPath), 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    return response('404 Not Found', 404);
});