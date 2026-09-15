<?php

/**
 * YXShop 授权配置
 *
 * 版本层级：open_source < commercial < enterprise < saas
 * 开源版包含此文件但所有高级功能默认关闭。
 */

return [
    // 当前版本（从 .env 读取）
    'edition' => env('APP_EDITION', 'open_source'),

    // 授权码（商业版/企业版必填）
    'key' => env('LICENSE_KEY', ''),

    // 授权验证服务器
    'verify_server' => env('LICENSE_SERVER', 'https://license.yxshop.com'),

    // 离线降级天数（验证服务器不可达时，本地缓存有效天数）
    'offline_grace_days' => 30,

    // 试用模式
    'trial' => [
        'enabled' => env('LICENSE_TRIAL_ENABLED', false),
        'days' => 14,  // 试用天数
    ],

    // 版本权重（用于比较版本高低）
    'edition_weights' => [
        'open_source' => 0,
        'commercial' => 1,
        'enterprise' => 2,
        'saas' => 3,
    ],

    // 各版本功能模块映射
    'features' => [
        // ===== 商业版功能 =====
        'distribution' => [
            'name' => '分销裂变',
            'min_edition' => 'commercial',
        ],
        'seckill_pro' => [
            'name' => '限时秒杀 Pro',
            'min_edition' => 'commercial',
        ],
        'membership_card' => [
            'name' => '高级会员卡',
            'min_edition' => 'commercial',
        ],
        'live_stream' => [
            'name' => '直播带货',
            'min_edition' => 'commercial',
        ],
        'community_care' => [
            'name' => '社区养老',
            'min_edition' => 'commercial',
        ],
        'gift_activity' => [
            'name' => '赠品活动',
            'min_edition' => 'commercial',
        ],
        'group_buy' => [
            'name' => '拼团',
            'min_edition' => 'commercial',
        ],
        'marketing_game' => [
            'name' => '营销游戏',
            'min_edition' => 'commercial',
        ],

        // ===== 企业版功能 =====
        'multi_shop' => [
            'name' => '多店铺系统',
            'min_edition' => 'enterprise',
        ],
        'supply_chain' => [
            'name' => '供应链管理',
            'min_edition' => 'enterprise',
        ],
        'audit_trail' => [
            'name' => '企业审计追踪',
            'min_edition' => 'enterprise',
        ],
        'fine_grained_auth' => [
            'name' => '细粒度权限',
            'min_edition' => 'enterprise',
        ],
        'item_audit' => [
            'name' => '商品审核流程',
            'min_edition' => 'enterprise',
        ],
        'analytics_pro' => [
            'name' => '数据分析增强',
            'min_edition' => 'enterprise',
        ],
        'agent_profit' => [
            'name' => '代理分润',
            'min_edition' => 'enterprise',
        ],
        'saas_platform' => [
            'name' => 'SaaS托管平台',
            'min_edition' => 'saas',
        ],
    ],

    // 路由前缀 → 功能模块映射（LicenseMiddleware 用于拦截检查）
    'route_module_map' => [
        // ===== 商业版后台路由前缀 =====
        '/admin/api/agents' => 'distribution',
        '/admin/api/agent-capitals' => 'distribution',
        '/admin/api/agent-withdraws' => 'distribution',
        '/admin/api/dealer-referees' => 'distribution',
        '/admin/api/order-agents' => 'distribution',
        '/admin/api/profit-logs' => 'distribution',
        '/admin/api/cards' => 'membership_card',
        '/admin/api/seckill-sessions' => 'seckill_pro',
        '/admin/api/seckill-items' => 'seckill_pro',
        '/admin/api/group-buys' => 'group_buy',
        '/admin/api/group-buy-members' => 'group_buy',
        '/admin/api/gift-activities' => 'gift_activity',
        '/admin/api/gift-activity-items' => 'gift_activity',
        '/admin/api/community' => 'community_care',
        '/admin/api/live-rooms' => 'live_stream',

        // ===== C端商业版路由前缀 =====
        '/api/v1/group-buy' => 'group_buy',
        '/api/v1/seckill' => 'seckill_pro',
        '/api/v1/community' => 'community_care',
        '/api/v1/membership' => 'membership_card',
        '/api/v1/live' => 'live_stream',

        // ===== 企业版后台路由前缀 =====
        '/admin/api/merchant-applies' => 'multi_shop',
        '/admin/api/invoice-headers' => 'multi_shop',
        '/admin/api/invoice-records' => 'multi_shop',
        '/admin/api/shops' => 'multi_shop',
        '/admin/api/shop-levels' => 'multi_shop',
        '/admin/api/shop-settlements' => 'multi_shop',
        '/admin/api/suppliers' => 'supply_chain',
        '/admin/api/warehouses' => 'supply_chain',
        '/admin/api/stock-transfers' => 'supply_chain',
        '/admin/api/purchase-orders' => 'supply_chain',
        '/admin/api/settlements' => 'supply_chain',
        '/admin/api/inventory' => 'supply_chain',
        '/admin/api/data-change-logs' => 'audit_trail',
        '/admin/api/audit-reports' => 'audit_trail',
        '/admin/api/audit-trail' => 'audit_trail',
        '/admin/api/permissions/config' => 'fine_grained_auth',
        '/admin/api/permissions/config-tree' => 'fine_grained_auth',
        '/admin/api/item-audit-logs' => 'item_audit',
        '/admin/api/analytics' => 'analytics_pro',
        '/admin/api/customers' => 'analytics_pro',
        '/admin/api/fine-grained-permissions' => 'fine_grained_auth',
        '/admin/api/item-audit' => 'item_audit',
        '/admin/api/analytics-pro' => 'analytics_pro',
        '/admin/api/agent-levels' => 'agent_profit',
        '/admin/api/agent-level-logs' => 'agent_profit',
        '/admin/api/agent-profit-rules' => 'agent_profit',
        '/admin/api/agent-profit-logs' => 'agent_profit',
        '/admin/api/agent-withdraw-audits' => 'agent_profit',
        '/admin/api/agent-ranking' => 'agent_profit',

        // ===== C端企业版路由前缀 =====
        '/api/v1/merchant-apply' => 'multi_shop',
        '/api/v1/agent' => 'agent_profit',

        // ===== SaaS 平台管理路由前缀（仅 SaaS 版本可访问） =====
        '/admin/api/saas' => 'saas_platform',
    ],
];
