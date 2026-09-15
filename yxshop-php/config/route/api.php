<?php

use support\Request;
use Webman\Route;

// API接口端路由配置（小程序/APP）
Route::group('/api/v1', function () {
    
    // 应用版本管理
    Route::group('/version', function () {
        // 获取最新版本
        Route::get('/latest', [app\api\v1\controller\AppVersionController::class, 'getLatest']);
        
        // 检查是否需要更新
        Route::get('/check-update', [app\api\v1\controller\AppVersionController::class, 'checkUpdate']);
        
        // 获取强制更新版本
        Route::get('/force-update', [app\api\v1\controller\AppVersionController::class, 'getForceUpdate']);
        
        // 获取版本信息
        Route::get('/info', [app\api\v1\controller\AppVersionController::class, 'getInfo']);
    });

    // 小程序首页数据
    Route::get('/mini-page/home', [app\api\controller\MiniPageController::class, 'home']);

    // 认证管理
    Route::group('/auth', function () {
        // 用户登录
        Route::post('/login', [app\api\v1\controller\AuthController::class, 'login']);

        // 用户注册
        Route::post('/register', [app\api\v1\controller\AuthController::class, 'register']);

        // 用户登出
        Route::post('/logout', [app\api\v1\controller\AuthController::class, 'logout']);

        // 刷新Token
        Route::post('/refresh-token', [app\api\v1\controller\AuthController::class, 'refreshToken']);

        // 忘记密码
        Route::post('/forgot-password', [app\api\v1\controller\AuthController::class, 'forgotPassword']);

        // 重置密码
        Route::post('/reset-password', [app\api\v1\controller\AuthController::class, 'resetPassword']);

        // 微信小程序一键登录（无需鉴权）
        Route::post('/wx-login', [app\api\v1\controller\AuthController::class, 'wxLogin']);

        // 同步微信用户资料（需要登录）
        Route::post('/wx-profile', [app\api\v1\controller\AuthController::class, 'updateWxProfile'])
            ->middleware([app\middleware\ApiAuthMiddleware::class]);

        // 微信手机号一键绑定（需要登录）
        Route::post('/bind-phone', [app\api\v1\controller\AuthController::class, 'bindPhone'])
            ->middleware([app\middleware\ApiAuthMiddleware::class]);
    });
    
    // 商品管理
    Route::group('/item', function () {
        Route::get('/list', [app\api\v1\controller\ItemController::class, 'getList']);
        Route::get('/detail', [app\api\v1\controller\ItemController::class, 'getDetail']);
        Route::get('/hot', [app\api\v1\controller\ItemController::class, 'getHot']);
        Route::get('/recommended', [app\api\v1\controller\ItemController::class, 'getRecommended']);
        Route::get('/new', [app\api\v1\controller\ItemController::class, 'getNew']);
        Route::get('/categories', [app\api\v1\controller\ItemController::class, 'getCategories']);
        Route::get('/search', [app\api\v1\controller\ItemController::class, 'search']);
        // P0 新增：商品评价、相关推荐、浏览足迹
        Route::get('/reviews', [app\api\v1\controller\ItemController::class, 'getReviews']);
        Route::get('/related', [app\api\v1\controller\ItemController::class, 'getRelated']);
        Route::post('/view', [app\api\v1\controller\ItemController::class, 'view'])
            ->middleware([app\middleware\ApiAuthMiddleware::class]);
        // P1 新增：商品咨询/问答
        Route::get('/consultations', [app\api\v1\controller\ItemController::class, 'getConsultations']);
        Route::post('/consult', [app\api\v1\controller\ItemController::class, 'consult'])
            ->middleware([app\middleware\ApiAuthMiddleware::class]);
    });

    // 用户信息
    Route::group('/user', function () {
        // 获取用户信息
        Route::get('/info', [app\api\v1\controller\UserController::class, 'getInfo']);
        
        // 更新用户信息
        Route::post('/update-info', [app\api\v1\controller\UserController::class, 'updateInfo']);
        
        // 更新用户头像
        Route::post('/update-avatar', [app\api\v1\controller\UserController::class, 'updateAvatar']);
        
        // 修改密码
        Route::post('/change-password', [app\api\v1\controller\UserController::class, 'changePassword']);
        
        // 获取用户订单
        Route::get('/orders', [app\api\v1\controller\UserController::class, 'getOrders']);
        
        // 获取用户收藏
        Route::get('/favorites', [app\api\v1\controller\UserController::class, 'getFavorites']);

        // P1 新增：用户综合信息（个人中心聚合）
        Route::get('/profile', [app\api\v1\controller\UserController::class, 'profile']);

        // P1 新增：浏览足迹
        Route::get('/footprint', [app\api\v1\controller\UserController::class, 'footprint']);
        Route::post('/footprint-remove', [app\api\v1\controller\UserController::class, 'footprintRemove']);
        Route::post('/footprint-clear', [app\api\v1\controller\UserController::class, 'footprintClear']);

        // P3 新增：搜索历史
        Route::get('/search-history', [app\api\v1\controller\UserController::class, 'searchHistory']);
        Route::post('/search-history-clear', [app\api\v1\controller\UserController::class, 'searchHistoryClear']);
        Route::post('/search-history-remove', [app\api\v1\controller\UserController::class, 'searchHistoryRemove']);

        // P3 新增：用户等级详情
        Route::get('/level', [app\api\v1\controller\UserLevelController::class, 'detail']);
    })->middleware([app\middleware\ApiAuthMiddleware::class]);
    
    // 订单管理
    Route::group('/order', function () {
        // 创建订单
        Route::post('/create', [app\api\v1\controller\OrderController::class, 'create']);

        // 获取订单列表（支持 tab 过滤）
        Route::get('/list', [app\api\v1\controller\OrderController::class, 'getList']);

        // P0 新增：订单各状态数量（tab 角标）
        Route::get('/status-counts', [app\api\v1\controller\OrderController::class, 'statusCounts']);

        // 获取订单详情
        Route::get('/detail', [app\api\v1\controller\OrderController::class, 'getDetail']);

        // 取消订单
        Route::post('/cancel', [app\api\v1\controller\OrderController::class, 'cancel']);

        // 确认收货
        Route::post('/confirm', [app\api\v1\controller\OrderController::class, 'confirm']);

        // 申请退款
        Route::post('/refund', [app\api\v1\controller\OrderController::class, 'refund']);

        // 评价订单
        Route::post('/review', [app\api\v1\controller\OrderController::class, 'review']);

        // 退款详情
        Route::get('/refund-detail', [app\api\v1\controller\OrderController::class, 'refundDetail']);

        // P1 新增：再次购买
        Route::post('/reorder', [app\api\v1\controller\OrderController::class, 'reorder']);

        // P1 新增：订单物流
        Route::get('/logistics', [app\api\v1\controller\OrderController::class, 'logistics']);

        // P1 新增：提醒发货
        Route::post('/remind-ship', [app\api\v1\controller\OrderController::class, 'remindShip']);

        // P1 新增：删除订单
        Route::post('/delete', [app\api\v1\controller\OrderController::class, 'delete']);
    })->middleware([app\middleware\ApiAuthMiddleware::class]);
    
    // 支付管理
    Route::group('/payment', function () {
        // 创建支付订单
        Route::post('/create', [app\api\v1\controller\PaymentController::class, 'create']);
        
        // 支付回调
        Route::post('/callback', [app\api\v1\controller\PaymentController::class, 'callback']);
        
        // 查询支付状态
        Route::get('/status', [app\api\v1\controller\PaymentController::class, 'getStatus']);
        
        // 申请退款
        Route::post('/refund', [app\api\v1\controller\PaymentController::class, 'refund']);
    })->middleware([app\middleware\ApiAuthMiddleware::class]);
    
    // 购物车
    Route::group('/cart', function () {
        // 获取购物车
        Route::get('/list', [app\api\v1\controller\CartController::class, 'getList']);

        // P0 新增：购物车数量徽标（轻量接口）
        Route::get('/count', [app\api\v1\controller\CartController::class, 'getCount']);

        // P0 新增：结算预览
        Route::get('/checkout-preview', [app\api\v1\controller\CartController::class, 'checkoutPreview']);

        // 添加商品到购物车
        Route::post('/add', [app\api\v1\controller\CartController::class, 'add']);

        // 更新购物车商品数量
        Route::post('/update', [app\api\v1\controller\CartController::class, 'update']);

        // 删除购物车商品
        Route::post('/remove', [app\api\v1\controller\CartController::class, 'remove']);

        // 清空购物车
        Route::post('/clear', [app\api\v1\controller\CartController::class, 'clear']);

        // P0 新增：选中/取消选中
        Route::post('/select', [app\api\v1\controller\CartController::class, 'select']);

        // P0 新增：全选/取消全选
        Route::post('/select-all', [app\api\v1\controller\CartController::class, 'selectAll']);
    })->middleware([app\middleware\ApiAuthMiddleware::class]);
    
    // 收藏管理
    Route::group('/favorite', function () {
        // 获取收藏列表
        Route::get('/list', [app\api\v1\controller\FavoriteController::class, 'getList']);
        
        // 添加收藏
        Route::post('/add', [app\api\v1\controller\FavoriteController::class, 'add']);
        
        // 取消收藏
        Route::post('/remove', [app\api\v1\controller\FavoriteController::class, 'remove']);
        
        // 检查是否已收藏
        Route::get('/check', [app\api\v1\controller\FavoriteController::class, 'check']);
    })->middleware([app\middleware\ApiAuthMiddleware::class]);
    
    // 地址管理
    Route::group('/address', function () {
        // 获取地址列表
        Route::get('/list', [app\api\v1\controller\AddressController::class, 'getList']);
        
        // 添加地址
        Route::post('/add', [app\api\v1\controller\AddressController::class, 'add']);
        
        // 更新地址
        Route::post('/update', [app\api\v1\controller\AddressController::class, 'update']);
        
        // 删除地址
        Route::post('/delete', [app\api\v1\controller\AddressController::class, 'delete']);
        
        // 设置默认地址
        Route::post('/set-default', [app\api\v1\controller\AddressController::class, 'setDefault']);
    })->middleware([app\middleware\ApiAuthMiddleware::class]);
    
    // 消息通知
    Route::group('/notification', function () {
        // 获取消息列表
        Route::get('/list', [app\api\v1\controller\NotificationController::class, 'getList']);
        
        // 标记消息为已读
        Route::post('/mark-read', [app\api\v1\controller\NotificationController::class, 'markRead']);
        
        // 批量标记消息为已读
        Route::post('/mark-multiple-read', [app\api\v1\controller\NotificationController::class, 'markMultipleRead']);

        // 获取未读消息数量
        Route::get('/unread-count', [app\api\v1\controller\NotificationController::class, 'getUnreadCount']);
    })->middleware([app\middleware\ApiAuthMiddleware::class]);
    
    // 文件上传
    Route::group('/upload', function () {
        // 上传图片
        Route::post('/image', [app\api\v1\controller\UploadController::class, 'uploadImage']);
        
        // 上传文件
        Route::post('/file', [app\api\v1\controller\UploadController::class, 'uploadFile']);
    })->middleware([app\middleware\ApiAuthMiddleware::class]);
    
    // 系统配置
    Route::group('/config', function () {
        // 获取系统配置
        Route::get('/system', [app\api\v1\controller\ConfigController::class, 'getSystemConfig']);

        // 获取主题配置
        Route::get('/theme', [app\api\v1\controller\ConfigController::class, 'getThemeConfig']);

        // 获取支付配置
        Route::get('/payment', [app\api\v1\controller\ConfigController::class, 'getPaymentConfig']);

        // 搜索热词
        Route::get('/search-hot-words', [app\api\v1\controller\ConfigController::class, 'getSearchHotWords']);
    });

    // P0 新增：分类管理（C 端）
    Route::group('/category', function () {
        Route::get('/tree', [app\api\v1\controller\CategoryController::class, 'tree']);
        Route::get('/items', [app\api\v1\controller\CategoryController::class, 'items']);
        Route::get('/brands', [app\api\v1\controller\CategoryController::class, 'brands']);
    });

    // 品牌管理（C 端）
    Route::group('/brand', function () {
        Route::get('/list', [app\api\v1\controller\BrandController::class, 'list']);
    });

    // 优惠券
    Route::group('/coupon', function () {
        Route::get('/list', [app\api\v1\controller\CouponController::class, 'getList']);
        // P2 新增：可领优惠券列表
        Route::get('/available', [app\api\v1\controller\CouponController::class, 'available']);
        // P2 新增：领取优惠券
        Route::post('/claim', [app\api\v1\controller\CouponController::class, 'claim'])
            ->middleware([app\middleware\ApiAuthMiddleware::class]);
        // P2 新增：优惠券统计
        Route::get('/stats', [app\api\v1\controller\CouponController::class, 'stats'])
            ->middleware([app\middleware\ApiAuthMiddleware::class]);
        // P2 增强：我的优惠券
        Route::get('/my-list', [app\api\v1\controller\CouponController::class, 'myList'])
            ->middleware([app\middleware\ApiAuthMiddleware::class]);
    });

    // P2 新增：签到
    Route::group('/sign', function () {
        Route::get('/status', [app\api\v1\controller\SignController::class, 'status']);
        Route::post('/do', [app\api\v1\controller\SignController::class, 'do']);
        Route::get('/records', [app\api\v1\controller\SignController::class, 'records']);
    })->middleware([app\middleware\ApiAuthMiddleware::class]);

    // 积分商品（增强）
    Route::group('/point-item', function () {
        Route::get('/list', [app\api\v1\controller\PointExchangeController::class, 'list']);
        // P2 新增：积分兑换
        Route::post('/exchange', [app\api\v1\controller\PointExchangeController::class, 'exchange'])
            ->middleware([app\middleware\ApiAuthMiddleware::class]);
        // P2 新增：兑换记录
        Route::get('/records', [app\api\v1\controller\PointExchangeController::class, 'records'])
            ->middleware([app\middleware\ApiAuthMiddleware::class]);
    });

    // P2 新增：余额管理
    Route::group('/user', function () {
        Route::get('/balance', [app\api\v1\controller\BalanceController::class, 'balance']);
        Route::get('/money-log', [app\api\v1\controller\BalanceController::class, 'moneyLog']);
        Route::get('/money-stats', [app\api\v1\controller\BalanceController::class, 'moneyStats']);
        // P2 新增：积分流水
        Route::get('/integral-log', [app\api\v1\controller\PointExchangeController::class, 'integralLog']);
        // P2 新增：积分统计
        Route::get('/integral-stats', [app\api\v1\controller\PointExchangeController::class, 'integralStats']);
    })->middleware([app\middleware\ApiAuthMiddleware::class]);

    // P2 新增：充值
    Route::group('/recharge', function () {
        Route::get('/packages', [app\api\v1\controller\BalanceController::class, 'packages']);
        Route::post('/create', [app\api\v1\controller\BalanceController::class, 'create']);
        Route::get('/records', [app\api\v1\controller\BalanceController::class, 'records']);
    })->middleware([app\middleware\ApiAuthMiddleware::class]);

    // P2 新增：用户反馈
    Route::group('/feedback', function () {
        Route::post('/submit', [app\api\v1\controller\FeedbackController::class, 'submit']);
        Route::get('/my-list', [app\api\v1\controller\FeedbackController::class, 'myList']);
    })->middleware([app\middleware\ApiAuthMiddleware::class]);

    // 发票管理（企业版功能，已移至 enterprise 路由文件，通过 is_file 条件加载）
    // 合并企业版后自动注入，开源版跳过

    // 合同管理
    Route::group('/contract', function () {
        Route::get('/my-list', [app\api\v1\controller\ContractController::class, 'myList']);
        Route::get('/detail', [app\api\v1\controller\ContractController::class, 'detail']);
        Route::get('/stats', [app\api\v1\controller\ContractController::class, 'stats']);
    })->middleware([app\middleware\ApiAuthMiddleware::class]);

    // 账号安全
    Route::group('/security', function () {
        Route::get('/info', [app\api\v1\controller\SecurityController::class, 'info']);
        Route::post('/deactivate', [app\api\v1\controller\SecurityController::class, 'deactivate']);
    })->middleware([app\middleware\ApiAuthMiddleware::class]);

    // 企业认证
    Route::group('/verify', function () {
        Route::get('/info', [app\api\v1\controller\VerifyController::class, 'info']);
        Route::post('/submit', [app\api\v1\controller\VerifyController::class, 'submit']);
    })->middleware([app\middleware\ApiAuthMiddleware::class]);

    // 积分中心 & 任务
    Route::group('/point', function () {
        // 积分商城首页
        Route::get('/center', [app\api\v1\controller\PointExchangeController::class, 'center']);
        // P3 新增：任务列表
        Route::get('/tasks', [app\api\v1\controller\PointTaskController::class, 'list']);
        // P3 新增：领取任务奖励
        Route::post('/tasks/claim', [app\api\v1\controller\PointTaskController::class, 'claim']);
    })->middleware([app\middleware\ApiAuthMiddleware::class]);

    // P3 新增：提现
    Route::group('/withdraw', function () {
        Route::post('/apply', [app\api\v1\controller\WithdrawController::class, 'apply']);
        Route::get('/records', [app\api\v1\controller\WithdrawController::class, 'records']);
    })->middleware([app\middleware\ApiAuthMiddleware::class]);

    // ===== 商业版 + 企业版 C端路由（条件加载） =====
    // 路由文件放在 commercial/route/ 和 enterprise/route/ 目录下
    // 开源版不含这些目录，自动跳过；升级时复制目录即可
    $commercialApiRoute = base_path() . '/commercial/route/api.php';
    $enterpriseApiRoute = base_path() . '/enterprise/route/api.php';
    if (is_file($commercialApiRoute)) {
        require_once $commercialApiRoute;
    }
    if (is_file($enterpriseApiRoute)) {
        require_once $enterpriseApiRoute;
    }

    // ==================== AI 能力模块（开源版增值服务） ====================

    // AI 智能客服
    Route::group('/ai', function () {
        // 发送消息（需要登录）
        Route::post('/chat', [app\api\v1\controller\AiController::class, 'chat']);
        // 获取会话历史（需要登录）
        Route::get('/chat-history', [app\api\v1\controller\AiController::class, 'chatHistory']);
        // 获取算力余额（需要登录）
        Route::get('/credits', [app\api\v1\controller\AiController::class, 'credits']);
    })->middleware([app\middleware\ApiAuthMiddleware::class]);

})->middleware([
    app\middleware\LicenseMiddleware::class,
    app\middleware\ApiCorsMiddleware::class,
    app\middleware\ApiRateLimitMiddleware::class
]); 


// V2版本API路由（新版本，可能包含破坏性变更）
Route::group('/api/v2', function () {
    // 认证相关（V2版本可能使用新的认证方式）
    Route::post('/auth/login', [app\api\v2\controller\AuthController::class, 'login']);
    Route::post('/auth/register', [app\api\v2\controller\AuthController::class, 'register']);
    Route::post('/auth/logout', [app\api\v2\controller\AuthController::class, 'logout']);
    Route::post('/auth/refresh-token', [app\api\v2\controller\AuthController::class, 'refreshToken']);
    Route::post('/auth/wx-login', [app\api\v2\controller\AuthController::class, 'wxLogin']);
    
    // 用户相关（V2版本可能包含新的字段或方法）
    Route::group('/user', function () {
        Route::get('/info', [app\api\v2\controller\UserController::class, 'getInfo']);
        Route::post('/update-info', [app\api\v2\controller\UserController::class, 'updateInfo']);
        Route::post('/update-avatar', [app\api\v2\controller\UserController::class, 'updateAvatar']);
        Route::post('/change-password', [app\api\v2\controller\UserController::class, 'changePassword']);
        Route::get('/orders', [app\api\v2\controller\UserController::class, 'getOrders']);
        Route::get('/favorites', [app\api\v2\controller\UserController::class, 'getFavorites']);
        // V2新增功能
        // Route::get('/preferences', [app\api\v2\controller\UserController::class, 'getPreferences']);
        // Route::post('/update-preferences', [app\api\v2\controller\UserController::class, 'updatePreferences']);
    })->middleware([app\middleware\ApiAuthMiddleware::class]);
    
    // 其他V2版本路由...
})->middleware([app\middleware\ApiCorsMiddleware::class]);


// 默认版本（重定向到V1）
Route::get('/api', function () {
    return json([
        'message' => '请指定API版本，例如: /api/v1 或 /api/v2',
        'versions' => [
            'v1' => '/api/v1',
            'v2' => '/api/v2'
        ],
        'current_stable' => 'v1',
        'latest' => 'v2'
    ]);
});

// 版本信息接口
Route::get('/api/versions', function () {
    return json([
        'versions' => [
            'v1' => [
                'status' => 'stable',
                'deprecated' => false,
                'sunset_date' => null,
                'features' => ['基础功能', '用户管理', '订单管理']
            ],
            'v2' => [
                'status' => 'beta',
                'deprecated' => false,
                'sunset_date' => null,
                'features' => ['基础功能', '用户管理', '订单管理', '高级功能', 'AI推荐']
            ]
        ],
        'recommendation' => '建议新项目使用V2版本，现有项目可继续使用V1版本'
    ]);
});

// 微信OAuth2.0回调路由（不需要鉴权）
Route::get('/wechat/callback/user', [app\controller\WechatCallbackController::class, 'userCallback']);
// 管理后台微信扫码登录回调已移除（开源版不需要）
// Route::get('/wechat/callback/admin', [app\controller\WechatCallbackController::class, 'adminCallback']);