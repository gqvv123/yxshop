<?php

return [
    // 支持的API版本
    'supported_versions' => ['v1', 'v2'],
    
    // 默认版本
    'default_version' => 'v1',
    
    // 版本状态配置
    'version_status' => [
        'v1' => [
            'status' => 'stable',
            'release_date' => '2024-01-01',
            'deprecated' => false,
            'sunset_date' => null,
            'support_level' => 'full',
            'breaking_changes' => false
        ],
        'v2' => [
            'status' => 'beta',
            'release_date' => '2024-06-01',
            'deprecated' => false,
            'sunset_date' => null,
            'support_level' => 'limited',
            'breaking_changes' => true
        ]
    ],
    
    // 版本特性配置
    'version_features' => [
        'v1' => [
            'description' => '稳定版本，包含基础功能',
            'features' => [
                'user_management' => ['基础用户管理', '认证授权'],
                'order_management' => ['基础订单管理', '支付集成'],
                'restaurant_management' => ['餐厅信息管理', '菜单管理'],
                'basic_search' => ['基础搜索功能', '分类筛选']
            ],
            'limitations' => [
                'no_ai_recommendations' => '无AI推荐功能',
                'limited_analytics' => '基础数据分析',
                'basic_notifications' => '基础通知系统'
            ]
        ],
        'v2' => [
            'description' => '测试版本，包含新功能和改进',
            'features' => [
                'user_management' => ['增强用户管理', '个性化设置', '偏好管理'],
                'order_management' => ['智能订单管理', '配送优化', '实时跟踪'],
                'restaurant_management' => ['高级餐厅管理', 'AI菜单推荐', '智能定价'],
                'advanced_search' => ['智能搜索', '语义理解', '个性化推荐'],
                'ai_features' => ['AI推荐系统', '智能客服', '预测分析'],
                'analytics' => ['高级数据分析', '实时监控', '业务洞察']
            ],
            'breaking_changes' => [
                'user.info' => [
                    'type' => 'field_addition',
                    'description' => '新增preferences和settings字段',
                    'impact' => 'low',
                    'migration' => '向后兼容，旧客户端可忽略新字段'
                ],
                'order.create' => [
                    'type' => 'parameter_addition',
                    'description' => '新增delivery_options和estimated_time参数',
                    'impact' => 'medium',
                    'migration' => '旧版本客户端需要更新以支持新参数'
                ]
            ]
        ]
    ],
    
    // 版本迁移配置
    'migration' => [
        'v1_to_v2' => [
            'supported' => true,
            'automated' => true,
            'guide_url' => '/api/docs/migration/v1-to-v2',
            'tools' => [
                'migration_script' => true,
                'data_converter' => true,
                'compatibility_checker' => true
            ]
        ]
    ],
    
    // 版本弃用计划
    'deprecation_plan' => [
        'v1' => [
            'deprecation_date' => '2025-01-01',
            'sunset_date' => '2025-12-31',
            'migration_deadline' => '2025-06-30',
            'notifications' => [
                'deprecation_warning' => '2024-07-01',
                'migration_reminder' => '2024-10-01',
                'final_warning' => '2024-12-01'
            ]
        ]
    ],
    
    // 版本兼容性配置
    'compatibility' => [
        'cross_version_support' => true,
        'backward_compatibility' => [
            'v2' => ['v1'] // V2版本向后兼容V1
        ],
        'forward_compatibility' => [
            'v1' => ['v2'] // V1版本向前兼容V2（部分功能）
        ]
    ],
    
    // 版本文档配置
    'documentation' => [
        'v1' => [
            'url' => '/api/docs/v1',
            'swagger' => '/api/docs/v1/swagger',
            'examples' => '/api/docs/v1/examples',
            'sdk' => [
                'php' => 'https://github.com/example/api-v1-php-sdk',
                'javascript' => 'https://github.com/example/api-v1-js-sdk',
                'python' => 'https://github.com/example/api-v1-python-sdk'
            ]
        ],
        'v2' => [
            'url' => '/api/docs/v2',
            'swagger' => '/api/docs/v2/swagger',
            'examples' => '/api/docs/v2/examples',
            'sdk' => [
                'php' => 'https://github.com/example/api-v2-php-sdk',
                'javascript' => 'https://github.com/example/api-v2-js-sdk',
                'python' => 'https://github.com/example/api-v2-python-sdk'
            ]
        ]
    ],
    
    // 版本监控配置
    'monitoring' => [
        'version_usage_tracking' => true,
        'performance_monitoring' => true,
        'error_tracking' => true,
        'deprecation_notifications' => true,
        'metrics' => [
            'request_count' => true,
            'response_time' => true,
            'error_rate' => true,
            'user_count' => true
        ]
    ]
]; 