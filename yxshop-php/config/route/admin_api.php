<?php

use Webman\Route;

Route::group('/admin/api', function () {

    Route::post('/login', [app\admin\controller\LoginController::class, 'login']);
    Route::get('/captcha', [app\admin\controller\LoginController::class, 'captcha']);
    Route::get('/captcha/puzzle', [app\admin\controller\LoginController::class, 'puzzleCaptcha']);
    // 微信扫码登录已移除（开源版不需要）
    // Route::post('/wechat/qrcode', [app\admin\controller\WechatAuthController::class, 'qrcode']);
    // Route::get('/wechat/scan-status', [app\admin\controller\WechatAuthController::class, 'scanStatus']);

    Route::group('', function () {

        Route::post('/logout', [app\admin\controller\LoginController::class, 'logout']);
        Route::get('/user/info', [app\admin\controller\LoginController::class, 'userInfo']);
        Route::put('/user/profile', [app\admin\controller\LoginController::class, 'updateProfile']);
        Route::post('/user/change-password', [app\admin\controller\LoginController::class, 'changePassword']);
        Route::post('/user/force-change-password', [app\admin\controller\LoginController::class, 'forceChangePassword']);
        // 2FA 双因素认证已移除（开源版不需要，属于商业版等保功能）
        // Route::get('/user/2fa/status', [app\admin\controller\LoginController::class, 'twoFactorStatus']);
        // Route::post('/user/2fa/setup', [app\admin\controller\LoginController::class, 'twoFactorSetup']);
        // Route::post('/user/2fa/enable', [app\admin\controller\LoginController::class, 'twoFactorEnable']);
        // Route::post('/user/2fa/disable', [app\admin\controller\LoginController::class, 'twoFactorDisable']);
        // 微信扫码绑定/解绑已移除（开源版不需要）
        // Route::get('/wechat/bind-status', [app\admin\controller\WechatAuthController::class, 'bindStatus']);
        // Route::post('/wechat/bind-qrcode', [app\admin\controller\WechatAuthController::class, 'bindQrcode']);
        // Route::get('/wechat/bind-status/poll', [app\admin\controller\WechatAuthController::class, 'bindStatusPoll']);
        // Route::post('/wechat/unbind', [app\admin\controller\WechatAuthController::class, 'unbind']);

        // 公共接口
        Route::get('/common/dict/all', [app\admin\controller\CommonController::class, 'dictAll']);
        Route::post('/common/clear-cache', [app\admin\controller\CommonController::class, 'clearCache']);

        Route::get('/dashboard/statistics', [app\admin\controller\IndexController::class, 'statistics']);
        Route::get('/dashboard/charts', [app\admin\controller\IndexController::class, 'charts']);
        Route::get('/dashboard/recent-orders', [app\admin\controller\IndexController::class, 'recentOrders']);
        Route::get('/dashboard/recent-customers', [app\admin\controller\IndexController::class, 'recentCustomers']);

        // 商品管理（静态路由在前）
        Route::get('/items', [app\admin\controller\ItemController::class, 'index']);
        Route::post('/items', [app\admin\controller\ItemController::class, 'store']);
        Route::get('/items/select', [app\admin\controller\ItemController::class, 'select']);
        Route::get('/items/summary', [app\admin\controller\ItemController::class, 'summary']);
        Route::get('/items/categories', [app\admin\controller\CategoryController::class, 'index']);
        Route::get('/items/brands', [app\admin\controller\BrandController::class, 'index']);
        Route::post('/items/batch-status', [app\admin\controller\ItemController::class, 'batchStatus']);
        Route::post('/items/batch-delete', [app\admin\controller\ItemController::class, 'batchDelete']);
        Route::post('/items/batch-price', [app\admin\controller\ItemController::class, 'batchPrice']);
        Route::post('/items/batch-stock-adjust', [app\admin\controller\ItemController::class, 'batchStockAdjust']);
        Route::get('/items/export', [app\admin\controller\ItemController::class, 'export']);
        Route::get('/items/{id}/skus', [app\admin\controller\ItemController::class, 'skus']);
        Route::get('/items/{id}', [app\admin\controller\ItemController::class, 'show']);
        Route::put('/items/{id}', [app\admin\controller\ItemController::class, 'update']);
        Route::delete('/items/{id}', [app\admin\controller\ItemController::class, 'destroy']);
        Route::post('/items/{id}/status', [app\admin\controller\ItemController::class, 'updateStatus']);
        Route::post('/items/{id}/stock-adjust', [app\admin\controller\ItemController::class, 'stockAdjust']);
        Route::post('/items/{id}/duplicate', [app\admin\controller\ItemController::class, 'duplicate']);
        Route::get('/items/{id}/price-history', [app\admin\controller\ItemController::class, 'priceHistory']);

        // 订单管理（静态路由在前）
        Route::get('/orders', [app\admin\controller\OrderController::class, 'index']);
        Route::post('/orders/product-status', [app\admin\controller\OrderController::class, 'updateProductStatus']);
        Route::get('/orders/statistics', [app\admin\controller\OrderController::class, 'statistics']);
        Route::get('/orders/tracking', [app\admin\controller\OrderController::class, 'tracking']);
        Route::get('/orders/{id}/customer-profile', [app\admin\controller\OrderController::class, 'customerProfile']);
        Route::get('/orders/{id}/tracking', [app\admin\controller\OrderController::class, 'showTracking']);
        Route::get('/orders/{id}', [app\admin\controller\OrderController::class, 'show']);
        Route::post('/orders/{id}/status', [app\admin\controller\OrderController::class, 'updateStatus']);
        Route::post('/orders/{id}/change-price', [app\admin\controller\OrderController::class, 'changePrice']);
        Route::post('/orders/{id}/free-shipping', [app\admin\controller\OrderController::class, 'freeShipping']);
        Route::post('/orders/{id}/ship', [app\admin\controller\OrderController::class, 'ship']);
        Route::post('/orders/{id}/audit', [app\admin\controller\OrderController::class, 'audit']);
        Route::post('/orders/{id}/refund', [app\admin\controller\OrderController::class, 'refund']);
        Route::post('/orders/{id}/note', [app\admin\controller\OrderController::class, 'note']);
        Route::delete('/orders/{id}', [app\admin\controller\OrderController::class, 'destroy']);

        // 用户管理（静态路由在前）
        Route::get('/users', [app\admin\controller\UserController::class, 'index']);
        Route::get('/users/dashboard', [app\admin\controller\UserController::class, 'dashboard']);
        Route::get('/users/{id}/addresses', [app\admin\controller\UserController::class, 'addresses']);
        Route::get('/users/{id}', [app\admin\controller\UserController::class, 'show']);

        // 分类管理
        Route::get('/categories', [app\admin\controller\CategoryController::class, 'index']);
        Route::post('/categories', [app\admin\controller\CategoryController::class, 'store']);
        Route::get('/categories/{id}', [app\admin\controller\CategoryController::class, 'show']);
        Route::put('/categories/{id}', [app\admin\controller\CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [app\admin\controller\CategoryController::class, 'destroy']);
        Route::post('/categories/{id}/status', [app\admin\controller\CategoryController::class, 'status']);

        // 品牌管理（静态路由在前）
        Route::get('/brands', [app\admin\controller\BrandController::class, 'index']);
        Route::post('/brands', [app\admin\controller\BrandController::class, 'store']);
        Route::post('/brands/batch-status', [app\admin\controller\BrandController::class, 'batchStatus']);
        Route::post('/brands/batch-delete', [app\admin\controller\BrandController::class, 'batchDelete']);
        Route::get('/brands/{id}', [app\admin\controller\BrandController::class, 'show']);
        Route::put('/brands/{id}', [app\admin\controller\BrandController::class, 'update']);
        Route::delete('/brands/{id}', [app\admin\controller\BrandController::class, 'destroy']);
        Route::post('/brands/{id}/status', [app\admin\controller\BrandController::class, 'updateStatus']);

        // 优惠券管理
        Route::get('/coupons', [app\admin\controller\CouponController::class, 'index']);
        Route::post('/coupons', [app\admin\controller\CouponController::class, 'store']);
        Route::get('/coupons/{id}', [app\admin\controller\CouponController::class, 'show']);
        Route::put('/coupons/{id}', [app\admin\controller\CouponController::class, 'update']);
        Route::delete('/coupons/{id}', [app\admin\controller\CouponController::class, 'destroy']);

        // 应用管理
        Route::get('/apps', [app\admin\controller\AppController::class, 'index']);
        Route::post('/apps', [app\admin\controller\AppController::class, 'store']);
        Route::get('/apps/{id}', [app\admin\controller\AppController::class, 'show']);
        Route::put('/apps/{id}', [app\admin\controller\AppController::class, 'update']);
        Route::delete('/apps/{id}', [app\admin\controller\AppController::class, 'destroy']);
        Route::post('/apps/{id}/status', [app\admin\controller\AppController::class, 'updateStatus']);
        Route::post('/apps/{id}/reset-key', [app\admin\controller\AppController::class, 'resetAppKey']);

        // 权限管理
        Route::get('/permissions', [app\admin\controller\PermissionController::class, 'index']);

        // 角色管理
        Route::get('/roles', [app\admin\controller\RoleController::class, 'index']);
        Route::post('/roles', [app\admin\controller\RoleController::class, 'store']);
        Route::get('/roles/{id}', [app\admin\controller\RoleController::class, 'show']);
        Route::put('/roles/{id}', [app\admin\controller\RoleController::class, 'update']);
        Route::delete('/roles/{id}', [app\admin\controller\RoleController::class, 'destroy']);
        Route::get('/roles/{id}/permissions', [app\admin\controller\RoleController::class, 'permissions']);
        Route::post('/roles/{id}/permissions', [app\admin\controller\RoleController::class, 'assignPermissions']);

        // 管理员管理
        Route::get('/admins', [app\admin\controller\AdminController::class, 'index']);
        Route::post('/admins', [app\admin\controller\AdminController::class, 'store']);
        Route::get('/admins/{id}', [app\admin\controller\AdminController::class, 'show']);
        Route::put('/admins/{id}', [app\admin\controller\AdminController::class, 'update']);
        Route::delete('/admins/{id}', [app\admin\controller\AdminController::class, 'destroy']);
        Route::post('/admins/{id}/status', [app\admin\controller\AdminController::class, 'updateStatus']);
        Route::post('/admins/{id}/reset-password', [app\admin\controller\AdminController::class, 'resetPassword']);
        Route::post('/admins/{id}/unlock', [app\admin\controller\SecurityController::class, 'unlockAdmin']);

        // 申请管理（静态路由在前）
        Route::get('/applies', [app\admin\controller\ApplyController::class, 'index']);
        Route::post('/applies/batch-process', [app\admin\controller\ApplyController::class, 'batchProcess']);
        Route::get('/applies/stats', [app\admin\controller\ApplyController::class, 'stats']);
        Route::get('/applies/export', [app\admin\controller\ApplyController::class, 'export']);
        Route::get('/applies/{id}', [app\admin\controller\ApplyController::class, 'show']);
        Route::put('/applies/{id}', [app\admin\controller\ApplyController::class, 'update']);
        Route::post('/applies/{id}/status', [app\admin\controller\ApplyController::class, 'updateStatus']);

        // 文件管理（静态路由在前）
        Route::get('/files', [app\admin\controller\FileController::class, 'index']);
        Route::post('/files/batch-move', [app\admin\controller\FileController::class, 'batchMove']);
        Route::post('/files/move-to-group', [app\admin\controller\FileController::class, 'moveToGroup']);
        Route::get('/files/groups', [app\admin\controller\FileController::class, 'groupIndex']);
        Route::post('/files/groups', [app\admin\controller\FileController::class, 'storeGroup']);
        Route::get('/files/stats', [app\admin\controller\FileController::class, 'stats']);
        Route::get('/files/{id}', [app\admin\controller\FileController::class, 'show']);
        Route::delete('/files/{id}', [app\admin\controller\FileController::class, 'delete']);
        Route::put('/files/groups/{id}', [app\admin\controller\FileController::class, 'updateGroup']);
        Route::delete('/files/groups/{id}', [app\admin\controller\FileController::class, 'deleteGroup']);

        // 上传
        Route::post('/upload/image', [app\admin\controller\UploadController::class, 'image']);
        Route::post('/upload/file', [app\admin\controller\UploadController::class, 'file']);

        // 营销管理
        Route::get('/marketing/summary', [app\admin\controller\MarketingController::class, 'summary']);
        Route::get('/marketing/statistics', [app\admin\controller\MarketingController::class, 'statistics']);
        Route::get('/marketing/campaigns', [app\admin\controller\MarketingController::class, 'campaigns']);
        Route::get('/marketing/coupons', [app\admin\controller\MarketingController::class, 'coupons']);
        Route::get('/marketing/channels', [app\admin\controller\MarketingController::class, 'channels']);
        Route::get('/marketing/insights', [app\admin\controller\MarketingController::class, 'insights']);
        Route::get('/marketing/export', [app\admin\controller\MarketingController::class, 'export']);
        Route::get('/marketing/promotions', [app\admin\controller\MarketingController::class, 'promotions']);
        Route::get('/marketing/notifications', [app\admin\controller\MarketingController::class, 'notifications']);

        // 支付管理（静态路由在前）
        Route::get('/payments', [app\admin\controller\PaymentController::class, 'index']);
        Route::post('/payments/batch-refund', [app\admin\controller\PaymentController::class, 'batchRefund']);
        Route::get('/payments/stats', [app\admin\controller\PaymentController::class, 'stats']);
        Route::get('/payments/export', [app\admin\controller\PaymentController::class, 'export']);
        Route::get('/payments/{id}', [app\admin\controller\PaymentController::class, 'show']);
        Route::post('/payments/{id}/refund', [app\admin\controller\PaymentController::class, 'refund']);
        Route::post('/payments/{id}/status', [app\admin\controller\PaymentController::class, 'updateStatus']);

        // 售后管理（静态路由在前）
        Route::get('/services', [app\admin\controller\ServiceController::class, 'index']);
        Route::post('/services/batch-process', [app\admin\controller\ServiceController::class, 'batchProcess']);
        Route::get('/services/export', [app\admin\controller\ServiceController::class, 'export']);
        Route::get('/services/stats', [app\admin\controller\ServiceController::class, 'stats']);
        Route::get('/services/{id}', [app\admin\controller\ServiceController::class, 'show']);
        Route::post('/services/{id}/handle', [app\admin\controller\ServiceController::class, 'handle']);
        Route::post('/services/{id}/status', [app\admin\controller\ServiceController::class, 'updateStatus']);

        // 快递管理
        Route::get('/expresses', [app\admin\controller\ExpressController::class, 'index']);
        Route::post('/expresses', [app\admin\controller\ExpressController::class, 'store']);
        Route::get('/expresses/{id}', [app\admin\controller\ExpressController::class, 'show']);
        Route::put('/expresses/{id}', [app\admin\controller\ExpressController::class, 'update']);
        Route::delete('/expresses/{id}', [app\admin\controller\ExpressController::class, 'destroy']);
        Route::post('/expresses/{id}/status', [app\admin\controller\ExpressController::class, 'updateStatus']);

        // 通知模板
        Route::get('/notification-templates', [app\admin\controller\NotificationTemplateController::class, 'index']);
        Route::post('/notification-templates', [app\admin\controller\NotificationTemplateController::class, 'store']);
        Route::get('/notification-templates/{id}', [app\admin\controller\NotificationTemplateController::class, 'show']);
        Route::put('/notification-templates/{id}', [app\admin\controller\NotificationTemplateController::class, 'update']);
        Route::delete('/notification-templates/{id}', [app\admin\controller\NotificationTemplateController::class, 'destroy']);
        Route::post('/notification-templates/{id}/status', [app\admin\controller\NotificationTemplateController::class, 'updateStatus']);
        Route::get('/notification-templates/{id}/preview', [app\admin\controller\NotificationTemplateController::class, 'preview']);
        Route::post('/notification-templates/{id}/test', [app\admin\controller\NotificationTemplateController::class, 'test']);

        // 日志管理（静态路由在前）
        Route::get('/logs/login', [app\admin\controller\LogController::class, 'login']);
        Route::get('/logs/admin', [app\admin\controller\LogController::class, 'admin']);
        Route::get('/logs/user', [app\admin\controller\LogController::class, 'user']);
        Route::get('/logs/system', [app\admin\controller\LogController::class, 'system']);
        Route::get('/logs/api', [app\admin\controller\LogController::class, 'api']);
        Route::post('/logs/batch-delete', [app\admin\controller\LogController::class, 'batchDelete']);
        Route::post('/logs/clear', [app\admin\controller\LogController::class, 'clear']);
        Route::get('/logs/export', [app\admin\controller\LogController::class, 'export']);
        Route::get('/logs/{id}', [app\admin\controller\LogController::class, 'show']);
        Route::delete('/logs/{id}', [app\admin\controller\LogController::class, 'delete']);

        // 财务管理
        Route::get('/finance/statistics', [app\admin\controller\FinanceController::class, 'statistics']);
        Route::get('/finance/transactions', [app\admin\controller\FinanceController::class, 'transactions']);

        // 数据报表
        Route::get('/reports/users', [app\admin\controller\ReportController::class, 'users']);
        Route::get('/reports/sales', [app\admin\controller\ReportController::class, 'sales']);
        Route::get('/reports/products', [app\admin\controller\ReportController::class, 'products']);

        // 文章管理
        Route::get('/articles', [app\admin\controller\ArticleController::class, 'index']);
        Route::post('/articles', [app\admin\controller\ArticleController::class, 'store']);
        Route::get('/articles/{id}', [app\admin\controller\ArticleController::class, 'show']);
        Route::put('/articles/{id}', [app\admin\controller\ArticleController::class, 'update']);
        Route::delete('/articles/{id}', [app\admin\controller\ArticleController::class, 'destroy']);
        Route::post('/articles/{id}/status', [app\admin\controller\ArticleController::class, 'updateStatus']);

        // 文章分类
        Route::get('/article-categories', [app\admin\controller\ArticleCategoryController::class, 'index']);
        Route::post('/article-categories', [app\admin\controller\ArticleCategoryController::class, 'store']);
        Route::put('/article-categories/{id}', [app\admin\controller\ArticleCategoryController::class, 'update']);
        Route::delete('/article-categories/{id}', [app\admin\controller\ArticleCategoryController::class, 'destroy']);

        // 页面管理
        Route::get('/app-pages', [app\admin\controller\AppPageController::class, 'index']);
        Route::post('/app-pages', [app\admin\controller\AppPageController::class, 'store']);
        Route::get('/app-pages/{id}', [app\admin\controller\AppPageController::class, 'show']);
        Route::put('/app-pages/{id}', [app\admin\controller\AppPageController::class, 'update']);
        Route::delete('/app-pages/{id}', [app\admin\controller\AppPageController::class, 'destroy']);
        Route::post('/app-pages/{id}/status', [app\admin\controller\AppPageController::class, 'updateStatus']);
        Route::get('/app-pages/{id}/preview', [app\admin\controller\AppPageController::class, 'preview']);

        // 内容页面管理
        Route::get('/content-pages', [app\admin\controller\ContentPageController::class, 'index']);
        Route::post('/content-pages', [app\admin\controller\ContentPageController::class, 'store']);
        Route::get('/content-pages/{id}', [app\admin\controller\ContentPageController::class, 'show']);
        Route::put('/content-pages/{id}', [app\admin\controller\ContentPageController::class, 'update']);
        Route::delete('/content-pages/{id}', [app\admin\controller\ContentPageController::class, 'destroy']);

        // 配送规则（静态路由在前）
        Route::get('/delivery-rules', [app\admin\controller\DeliveryRuleController::class, 'index']);
        Route::post('/delivery-rules', [app\admin\controller\DeliveryRuleController::class, 'store']);
        Route::get('/delivery-rules/stats', [app\admin\controller\DeliveryRuleController::class, 'stats']);
        Route::get('/delivery-rules/{id}', [app\admin\controller\DeliveryRuleController::class, 'show']);
        Route::put('/delivery-rules/{id}', [app\admin\controller\DeliveryRuleController::class, 'update']);
        Route::delete('/delivery-rules/{id}', [app\admin\controller\DeliveryRuleController::class, 'destroy']);
        Route::post('/delivery-rules/{id}/status', [app\admin\controller\DeliveryRuleController::class, 'updateStatus']);

        // 评价管理（静态路由在前）
        Route::get('/comments', [app\admin\controller\CommentController::class, 'index']);
        Route::get('/comments/stats', [app\admin\controller\CommentController::class, 'stats']);
        Route::get('/comments/export', [app\admin\controller\CommentController::class, 'export']);
        Route::get('/comments/{id}', [app\admin\controller\CommentController::class, 'show']);
        Route::post('/comments/{id}/reply', [app\admin\controller\CommentController::class, 'reply']);
        Route::post('/comments/{id}/status', [app\admin\controller\CommentController::class, 'updateStatus']);
        Route::delete('/comments/{id}', [app\admin\controller\CommentController::class, 'destroy']);

        // 系统设置
        Route::get('/settings', [app\admin\controller\SettingController::class, 'show']);
        Route::post('/settings', [app\admin\controller\SettingController::class, 'store']);
        Route::get('/settings/payment', [app\admin\controller\SettingController::class, 'showPayment']);
        Route::post('/settings/payment', [app\admin\controller\SettingController::class, 'storePayment']);
        Route::post('/settings/payment/cert', [app\admin\controller\SettingController::class, 'uploadCert']);
        Route::post('/settings/payment/test', [app\admin\controller\SettingController::class, 'testPayment']);
        Route::get('/settings/shipping', [app\admin\controller\SettingController::class, 'showShipping']);
        Route::post('/settings/shipping', [app\admin\controller\SettingController::class, 'storeShipping']);
        Route::get('/settings/notification', [app\admin\controller\SettingController::class, 'showNotification']);
        Route::post('/settings/notification', [app\admin\controller\SettingController::class, 'storeNotification']);
        Route::get('/settings/trade', [app\admin\controller\SettingController::class, 'showTrade']);
        Route::post('/settings/trade', [app\admin\controller\SettingController::class, 'storeTrade']);
        Route::get('/settings/team', [app\admin\controller\SettingController::class, 'teamIndex']);
        Route::put('/settings/team/{id:\d+}/role', [app\admin\controller\SettingController::class, 'teamUpdateRole']);
        Route::post('/settings/team/invite', [app\admin\controller\SettingController::class, 'teamInvite']);
        Route::post('/settings/team/{id:\d+}/status', [app\admin\controller\SettingController::class, 'teamUpdateStatus']);
        Route::delete('/settings/team/{id:\d+}', [app\admin\controller\SettingController::class, 'teamDestroy']);
        // API 密钥管理已移除（开源版不需要）
        // Route::get('/settings/api-keys', [app\admin\controller\SettingController::class, 'apiKeysIndex']);
        // Route::post('/settings/api-keys', [app\admin\controller\SettingController::class, 'apiKeysStore']);
        Route::get('/settings/regional', [app\admin\controller\SettingController::class, 'regionalShow']);
        Route::put('/settings/regional', [app\admin\controller\SettingController::class, 'regionalUpdate']);
        // 登录设置（会话超时、验证码类型）
        Route::get('/settings/security', [app\admin\controller\SecurityController::class, 'showSecurity']);
        Route::post('/settings/security', [app\admin\controller\SecurityController::class, 'storeSecurity']);
        Route::get('/settings/wechat', [app\admin\controller\SettingController::class, 'showWechat']);
        Route::post('/settings/wechat', [app\admin\controller\SettingController::class, 'storeWechat']);

        // 菜单管理
        Route::get('/menus', [app\admin\controller\AuthController::class, 'menuList']);
        Route::get('/menus/tree', [app\admin\controller\AuthController::class, 'menuTree']);
        Route::post('/menus', [app\admin\controller\AuthController::class, 'menuStore']);
        Route::get('/menus/{id}', [app\admin\controller\AuthController::class, 'menuRead']);
        Route::put('/menus/{id}', [app\admin\controller\AuthController::class, 'menuUpdate']);
        Route::delete('/menus/{id}', [app\admin\controller\AuthController::class, 'menuDestroy']);
        Route::post('/menus/sort', [app\admin\controller\AuthController::class, 'menuSort']);

        // 语言管理
        Route::get('/languages', [app\admin\controller\SettingController::class, 'languages']);
        Route::get('/language/list', [app\admin\controller\SettingController::class, 'languages']);
        Route::post('/languages', [app\admin\controller\SettingController::class, 'languageStore']);
        Route::put('/languages/{id}', [app\admin\controller\SettingController::class, 'languageUpdate']);
        Route::delete('/languages/{id}', [app\admin\controller\SettingController::class, 'languageDestroy']);
        Route::post('/languages/{id}/status', [app\admin\controller\SettingController::class, 'languageUpdateStatus']);

        // 权限树
        Route::get('/permissions/tree', [app\admin\controller\PermissionController::class, 'tree']);

        // 商品标签
        Route::get('/item-tags', [app\admin\controller\ItemTagController::class, 'index']);
        Route::post('/item-tags', [app\admin\controller\ItemTagController::class, 'store']);
        Route::get('/item-tags/{id}', [app\admin\controller\ItemTagController::class, 'show']);
        Route::put('/item-tags/{id}', [app\admin\controller\ItemTagController::class, 'update']);
        Route::delete('/item-tags/{id}', [app\admin\controller\ItemTagController::class, 'destroy']);

        // 商品类型
        Route::get('/item-types', [app\admin\controller\ItemTypeController::class, 'index']);
        Route::post('/item-types', [app\admin\controller\ItemTypeController::class, 'store']);
        Route::get('/item-types/{id}', [app\admin\controller\ItemTypeController::class, 'show']);
        Route::put('/item-types/{id}', [app\admin\controller\ItemTypeController::class, 'update']);
        Route::delete('/item-types/{id}', [app\admin\controller\ItemTypeController::class, 'destroy']);
        Route::post('/item-types/{id}/status', [app\admin\controller\ItemTypeController::class, 'status']);

        // 规格管理
        Route::get('/specs', [app\admin\controller\SpecController::class, 'index']);
        Route::post('/specs', [app\admin\controller\SpecController::class, 'store']);
        Route::get('/specs/{id:\d+}', [app\admin\controller\SpecController::class, 'show']);
        Route::put('/specs/{id:\d+}', [app\admin\controller\SpecController::class, 'update']);
        Route::delete('/specs/{id:\d+}', [app\admin\controller\SpecController::class, 'destroy']);
        Route::post('/specs/{id:\d+}/status', [app\admin\controller\SpecController::class, 'status']);

        // 库存预警（企业版功能，已移至 enterprise 路由文件，通过 is_file 条件加载）
        // 合并企业版后自动注入，开源版跳过

        // 订单物流
        Route::get('/order-deliveries', [app\admin\controller\OrderDeliveryController::class, 'index']);
        Route::get('/order-deliveries/{id}', [app\admin\controller\OrderDeliveryController::class, 'show']);
        Route::put('/order-deliveries/{id}', [app\admin\controller\OrderDeliveryController::class, 'updateStatus']);

        // 订单日志
        Route::get('/order-logs', [app\admin\controller\OrderLogController::class, 'index']);
        Route::get('/order-logs/{id}', [app\admin\controller\OrderLogController::class, 'show']);

        // 用户地址
        Route::get('/user-addresses', [app\admin\controller\UserAddressController::class, 'index']);
        Route::post('/user-addresses', [app\admin\controller\UserAddressController::class, 'store']);
        Route::get('/user-addresses/{id}', [app\admin\controller\UserAddressController::class, 'show']);
        Route::put('/user-addresses/{id}', [app\admin\controller\UserAddressController::class, 'update']);
        Route::delete('/user-addresses/{id}', [app\admin\controller\UserAddressController::class, 'destroy']);
        Route::post('/user-addresses/{id}/default', [app\admin\controller\UserAddressController::class, 'setDefault']);

        // 用户等级
        Route::get('/user-levels', [app\admin\controller\UserLevelController::class, 'index']);
        Route::post('/user-levels', [app\admin\controller\UserLevelController::class, 'store']);
        Route::get('/user-levels/{id}', [app\admin\controller\UserLevelController::class, 'show']);
        Route::put('/user-levels/{id}', [app\admin\controller\UserLevelController::class, 'update']);
        Route::delete('/user-levels/{id}', [app\admin\controller\UserLevelController::class, 'destroy']);
        Route::post('/user-levels/{id}/status', [app\admin\controller\UserLevelController::class, 'status']);

        // 余额日志
        Route::get('/user-money-logs', [app\admin\controller\UserMoneyLogController::class, 'index']);
        Route::get('/user-money-logs/{id}', [app\admin\controller\UserMoneyLogController::class, 'show']);

        // 促销活动
        Route::get('/promotions', [app\admin\controller\PromotionController::class, 'index']);
        Route::post('/promotions', [app\admin\controller\PromotionController::class, 'store']);
        Route::get('/promotions/{id}', [app\admin\controller\PromotionController::class, 'show']);
        Route::put('/promotions/{id}', [app\admin\controller\PromotionController::class, 'update']);
        Route::delete('/promotions/{id}', [app\admin\controller\PromotionController::class, 'destroy']);
        Route::post('/promotions/{id}/notify', [app\admin\controller\PromotionController::class, 'notify']);

        // 商品促销
        Route::get('/prom-items', [app\admin\controller\PromItemController::class, 'index']);
        Route::post('/prom-items', [app\admin\controller\PromItemController::class, 'store']);
        Route::get('/prom-items/{id}', [app\admin\controller\PromItemController::class, 'show']);
        Route::put('/prom-items/{id}', [app\admin\controller\PromItemController::class, 'update']);
        Route::delete('/prom-items/{id}', [app\admin\controller\PromItemController::class, 'destroy']);

        // 订单促销
        Route::get('/prom-orders', [app\admin\controller\PromOrderController::class, 'index']);
        Route::post('/prom-orders', [app\admin\controller\PromOrderController::class, 'store']);
        Route::get('/prom-orders/{id}', [app\admin\controller\PromOrderController::class, 'show']);
        Route::put('/prom-orders/{id}', [app\admin\controller\PromOrderController::class, 'update']);
        Route::delete('/prom-orders/{id}', [app\admin\controller\PromOrderController::class, 'destroy']);

        // 通知场景
        Route::get('/notification-scenes', [app\admin\controller\NotificationSceneController::class, 'index']);
        Route::post('/notification-scenes', [app\admin\controller\NotificationSceneController::class, 'store']);
        Route::get('/notification-scenes/{id}', [app\admin\controller\NotificationSceneController::class, 'show']);
        Route::put('/notification-scenes/{id}', [app\admin\controller\NotificationSceneController::class, 'update']);
        Route::delete('/notification-scenes/{id}', [app\admin\controller\NotificationSceneController::class, 'destroy']);

        // 通知发送记录
        Route::get('/notification-sends', [app\admin\controller\NotificationSendController::class, 'index']);
        Route::get('/notification-sends/{id}', [app\admin\controller\NotificationSendController::class, 'detail']);

        // 通知配置
        Route::get('/notification-configs', [app\admin\controller\NotificationConfigController::class, 'index']);
        Route::put('/notification-configs/{id}', [app\admin\controller\NotificationConfigController::class, 'update']);

        // 配送模板
        Route::get('/deliveries', [app\admin\controller\DeliveryController::class, 'index']);
        Route::post('/deliveries', [app\admin\controller\DeliveryController::class, 'store']);
        Route::get('/deliveries/{id}', [app\admin\controller\DeliveryController::class, 'show']);
        Route::put('/deliveries/{id}', [app\admin\controller\DeliveryController::class, 'update']);
        Route::delete('/deliveries/{id}', [app\admin\controller\DeliveryController::class, 'destroy']);

        // 广告管理
        Route::get('/advertisements', [app\admin\controller\AdvertisementController::class, 'index']);
        Route::post('/advertisements', [app\admin\controller\AdvertisementController::class, 'store']);
        Route::get('/advertisements/{id}', [app\admin\controller\AdvertisementController::class, 'show']);
        Route::put('/advertisements/{id}', [app\admin\controller\AdvertisementController::class, 'update']);
        Route::delete('/advertisements/{id}', [app\admin\controller\AdvertisementController::class, 'destroy']);

        // 货币管理
        Route::get('/currencies', [app\admin\controller\CurrencyController::class, 'index']);
        Route::post('/currencies', [app\admin\controller\CurrencyController::class, 'store']);
        Route::get('/currencies/{id}', [app\admin\controller\CurrencyController::class, 'show']);
        Route::put('/currencies/{id}', [app\admin\controller\CurrencyController::class, 'update']);
        Route::delete('/currencies/{id}', [app\admin\controller\CurrencyController::class, 'destroy']);

        // 地区管理（静态路由在前）
        Route::get('/regions', [app\admin\controller\RegionController::class, 'index']);
        Route::get('/regions/tree', [app\admin\controller\RegionController::class, 'tree']);
        Route::post('/regions', [app\admin\controller\RegionController::class, 'store']);
        Route::get('/regions/{id}', [app\admin\controller\RegionController::class, 'show']);
        Route::put('/regions/{id}', [app\admin\controller\RegionController::class, 'update']);
        Route::delete('/regions/{id}', [app\admin\controller\RegionController::class, 'destroy']);

        // ===== 商业版 + 企业版路由（条件加载） =====
        // 路由文件放在 commercial/route/ 和 enterprise/route/ 目录下
        // 开源版不含这些目录，自动跳过；升级时复制目录即可
        $commercialAdminRoute = base_path() . '/commercial/route/admin.php';
        $enterpriseAdminRoute = base_path() . '/enterprise/route/admin.php';
        $saasAdminRoute = base_path() . '/enterprise/route/saas_admin.php';
        if (is_file($commercialAdminRoute)) {
            require_once $commercialAdminRoute;
        }
        if (is_file($enterpriseAdminRoute)) {
            require_once $enterpriseAdminRoute;
        }
        if (is_file($saasAdminRoute)) {
            require_once $saasAdminRoute;
        }

        // 以下为开源版基础路由（保留在主文件中）

        // 支付日志
        Route::get('/payment-logs', [app\admin\controller\PaymentLogController::class, 'index']);
        Route::get('/payment-logs/{id}', [app\admin\controller\PaymentLogController::class, 'show']);

        // 通知黑名单
        Route::get('/notification-blacklists', [app\admin\controller\NotificationBlacklistController::class, 'index']);
        Route::post('/notification-blacklists', [app\admin\controller\NotificationBlacklistController::class, 'store']);
        Route::delete('/notification-blacklists/{id}', [app\admin\controller\NotificationBlacklistController::class, 'destroy']);

        // 通知变量
        Route::get('/notification-variables', [app\admin\controller\NotificationVariableController::class, 'index']);
        Route::post('/notification-variables', [app\admin\controller\NotificationVariableController::class, 'store']);
        Route::put('/notification-variables/{id}', [app\admin\controller\NotificationVariableController::class, 'update']);
        Route::delete('/notification-variables/{id}', [app\admin\controller\NotificationVariableController::class, 'destroy']);

        // 短信日志
        Route::get('/sms-logs', [app\admin\controller\SmsLogController::class, 'index']);
        Route::get('/sms-logs/{id}', [app\admin\controller\SmsLogController::class, 'show']);

        // 邮件日志
        Route::get('/email-logs', [app\admin\controller\EmailLogController::class, 'index']);
        Route::get('/email-logs/{id}', [app\admin\controller\EmailLogController::class, 'show']);

        // 文件日志
        Route::get('/file-logs', [app\admin\controller\FileLogController::class, 'index']);
        Route::get('/file-logs/{id}', [app\admin\controller\FileLogController::class, 'show']);

        // 用户优惠券
        Route::get('/user-coupons', [app\admin\controller\UserCouponController::class, 'index']);
        Route::get('/user-coupons/{id}', [app\admin\controller\UserCouponController::class, 'show']);

        // 用户日志
        Route::get('/user-logs', [app\admin\controller\UserLogController::class, 'index']);
        Route::get('/user-logs/{id}', [app\admin\controller\UserLogController::class, 'show']);

        // 商品搜索记录
        Route::get('/item-searches', [app\admin\controller\ItemSearchController::class, 'index']);
        Route::post('/item-searches/clear', [app\admin\controller\ItemSearchController::class, 'clear']);

        // 商品属性
        Route::get('/item-attributes', [app\admin\controller\ItemAttributeController::class, 'index']);
        Route::post('/item-attributes', [app\admin\controller\ItemAttributeController::class, 'store']);
        Route::get('/item-attributes/{id:\d+}', [app\admin\controller\ItemAttributeController::class, 'show']);
        Route::put('/item-attributes/{id:\d+}', [app\admin\controller\ItemAttributeController::class, 'update']);
        Route::delete('/item-attributes/{id:\d+}', [app\admin\controller\ItemAttributeController::class, 'destroy']);

        // 商品收藏
        Route::get('/item-favorites', [app\admin\controller\ItemFavoriteController::class, 'index']);
        Route::get('/item-favorites/{id}', [app\admin\controller\ItemFavoriteController::class, 'show']);

        // 定时任务（开源版基础功能）（静态路由在前）
        Route::get('/scheduled-tasks', [app\admin\controller\ScheduledTaskController::class, 'index']);
        Route::post('/scheduled-tasks/batch-delete', [app\admin\controller\ScheduledTaskController::class, 'batchDelete']);
        Route::post('/scheduled-tasks', [app\admin\controller\ScheduledTaskController::class, 'store']);
        Route::get('/scheduled-tasks/{id}', [app\admin\controller\ScheduledTaskController::class, 'show']);
        Route::put('/scheduled-tasks/{id}', [app\admin\controller\ScheduledTaskController::class, 'update']);
        Route::delete('/scheduled-tasks/{id}', [app\admin\controller\ScheduledTaskController::class, 'destroy']);
        Route::post('/scheduled-tasks/{id}/enable', [app\admin\controller\ScheduledTaskController::class, 'enable']);
        Route::post('/scheduled-tasks/{id}/disable', [app\admin\controller\ScheduledTaskController::class, 'disable']);
        Route::post('/scheduled-tasks/{id}/execute', [app\admin\controller\ScheduledTaskController::class, 'execute']);

        // 用户反馈（静态路由在前）
        Route::get('/user-feedbacks', [app\admin\controller\UserFeedbackController::class, 'index']);
        Route::post('/user-feedbacks/batch-delete', [app\admin\controller\UserFeedbackController::class, 'batchDelete']);
        Route::get('/user-feedbacks/{id}', [app\admin\controller\UserFeedbackController::class, 'show']);
        Route::delete('/user-feedbacks/{id}', [app\admin\controller\UserFeedbackController::class, 'destroy']);
        Route::post('/user-feedbacks/{id}/reply', [app\admin\controller\UserFeedbackController::class, 'reply']);
        Route::post('/user-feedbacks/{id}/close', [app\admin\controller\UserFeedbackController::class, 'close']);

        // 友情链接
        Route::get('/friendly-links', [app\admin\controller\FriendlyLinkController::class, 'index']);
        Route::post('/friendly-links', [app\admin\controller\FriendlyLinkController::class, 'store']);
        Route::get('/friendly-links/{id}', [app\admin\controller\FriendlyLinkController::class, 'show']);
        Route::put('/friendly-links/{id}', [app\admin\controller\FriendlyLinkController::class, 'update']);
        Route::delete('/friendly-links/{id}', [app\admin\controller\FriendlyLinkController::class, 'destroy']);
        Route::post('/friendly-links/{id}/enable', [app\admin\controller\FriendlyLinkController::class, 'enable']);
        Route::post('/friendly-links/{id}/disable', [app\admin\controller\FriendlyLinkController::class, 'disable']);

        // 充值套餐
        Route::get('/recharge-packages', [app\admin\controller\RechargePackageController::class, 'index']);
        Route::post('/recharge-packages', [app\admin\controller\RechargePackageController::class, 'store']);
        Route::get('/recharge-packages/{id}', [app\admin\controller\RechargePackageController::class, 'show']);
        Route::put('/recharge-packages/{id}', [app\admin\controller\RechargePackageController::class, 'update']);
        Route::delete('/recharge-packages/{id}', [app\admin\controller\RechargePackageController::class, 'destroy']);
        Route::post('/recharge-packages/{id}/enable', [app\admin\controller\RechargePackageController::class, 'enable']);
        Route::post('/recharge-packages/{id}/disable', [app\admin\controller\RechargePackageController::class, 'disable']);

        // 页面SEO
        Route::get('/page-seos', [app\admin\controller\PageSeoController::class, 'index']);
        Route::post('/page-seos', [app\admin\controller\PageSeoController::class, 'store']);
        Route::get('/page-seos/{id}', [app\admin\controller\PageSeoController::class, 'show']);
        Route::put('/page-seos/{id}', [app\admin\controller\PageSeoController::class, 'update']);
        Route::delete('/page-seos/{id}', [app\admin\controller\PageSeoController::class, 'destroy']);
        Route::post('/page-seos/upsert', [app\admin\controller\PageSeoController::class, 'upsert']);

        // ==================== 授权管理（开源版基础功能） ====================

        // 授权状态查询
        Route::get('/license/status', [app\admin\controller\LicenseController::class, 'status']);
        // 授权码激活
        Route::post('/license/activate', [app\admin\controller\LicenseController::class, 'activate']);
        // 申请试用
        Route::post('/license/trial', [app\admin\controller\LicenseController::class, 'startTrial']);
        // 功能对比表
        Route::get('/license/compare', [app\admin\controller\LicenseController::class, 'compare']);

        // ==================== AI 能力模块（开源版增值服务） ====================

        // AI 配置管理
        Route::get('/ai/config', [app\admin\controller\AiController::class, 'getConfig']);
        Route::post('/ai/config', [app\admin\controller\AiController::class, 'saveConfig']);

        // AI 算力包管理
        Route::get('/ai/credits', [app\admin\controller\AiController::class, 'credits']);
        Route::get('/ai/packages', [app\admin\controller\AiController::class, 'packages']);
        Route::post('/ai/credits/create', [app\admin\controller\AiController::class, 'createCredits']);

        // AI 使用统计与记录
        Route::get('/ai/stats', [app\admin\controller\AiController::class, 'stats']);
        Route::get('/ai/logs', [app\admin\controller\AiController::class, 'logs']);

        // AI 一键生成商品文案
        Route::post('/ai/copywriting', [app\admin\controller\AiController::class, 'copywriting']);

        // AI 数据分析报表
        Route::post('/ai/analytics', [app\admin\controller\AiController::class, 'analytics']);

    })->middleware([
        app\middleware\LicenseMiddleware::class,
        app\middleware\AdminAuthMiddleware::class,
        app\middleware\ApiAccessLogMiddleware::class,
        app\middleware\OperationLogMiddleware::class,
    ]);

    // 小程序装修
    Route::get('/mini-page/pages', [app\admin\controller\MiniPageController::class, 'index']);
    Route::post('/mini-page/pages', [app\admin\controller\MiniPageController::class, 'store']);
    Route::get('/mini-page/pages/{id}', [app\admin\controller\MiniPageController::class, 'show']);
    Route::post('/mini-page/pages/{id}', [app\admin\controller\MiniPageController::class, 'update']);
    Route::delete('/mini-page/pages/{id}', [app\admin\controller\MiniPageController::class, 'destroy']);
    Route::post('/mini-page/pages/{id}/publish', [app\admin\controller\MiniPageController::class, 'publish']);
    Route::post('/mini-page/pages/{id}/unpublish', [app\admin\controller\MiniPageController::class, 'unpublish']);
    Route::get('/mini-page/component-schemas', [app\admin\controller\MiniPageController::class, 'componentSchemas']);
    Route::get('/mini-page/themes', [app\admin\controller\MiniThemeController::class, 'index']);
    Route::post('/mini-page/themes', [app\admin\controller\MiniThemeController::class, 'store']);
    Route::post('/mini-page/themes/{id}', [app\admin\controller\MiniThemeController::class, 'update']);
    Route::delete('/mini-page/themes/{id}', [app\admin\controller\MiniThemeController::class, 'destroy']);
    Route::post('/mini-page/themes/{id}/apply', [app\admin\controller\MiniThemeController::class, 'apply']);
    Route::get('/mini-page/tabbar', [app\admin\controller\MiniTabBarController::class, 'show']);
    Route::post('/mini-page/tabbar', [app\admin\controller\MiniTabBarController::class, 'update']);
    Route::get('/mini-page/pages/{id}/versions', [app\admin\controller\MiniPageVersionController::class, 'index']);
    Route::get('/mini-page/pages/{id}/versions/{versionId}', [app\admin\controller\MiniPageVersionController::class, 'show']);
    Route::post('/mini-page/pages/{id}/versions/{versionId}/rollback', [app\admin\controller\MiniPageVersionController::class, 'rollback']);
    Route::get('/mini-page/templates', [app\admin\controller\MiniPageTemplateController::class, 'index']);
    Route::post('/mini-page/templates', [app\admin\controller\MiniPageTemplateController::class, 'store']);
    Route::post('/mini-page/templates/{id}/create-page', [app\admin\controller\MiniPageTemplateController::class, 'createPage']);
    Route::delete('/mini-page/templates/{id}', [app\admin\controller\MiniPageTemplateController::class, 'destroy']);
    Route::get('/mini-page/builtin-templates', [app\admin\controller\MiniPageTemplateController::class, 'builtinTemplates']);
    Route::get('/mini-page/builtin-templates/{templateId}', [app\admin\controller\MiniPageTemplateController::class, 'builtinTemplateDetail']);
    Route::post('/mini-page/builtin-templates/{templateId}/import', [app\admin\controller\MiniPageTemplateController::class, 'importBuiltinTemplate']);
    Route::post('/mini-page/builtin-templates/{templateId}/create-page', [app\admin\controller\MiniPageTemplateController::class, 'createPageFromBuiltin']);

    // 社区养老模块路由已迁移到 commercial_admin.php

})->middleware([
    app\middleware\LicenseMiddleware::class,
    app\middleware\AdminCorsMiddleware::class,
]);
