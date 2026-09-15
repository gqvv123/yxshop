<?php

namespace app\model;

/**
 * AI 使用记录模型
 *
 * 记录每次 AI 调用的详细信息（算力消耗、token、耗时等）
 */
class AiUsageLog extends BaseModel
{
    protected $table = 'yxshop_ai_usage_logs';

    protected $fillable = [
        'app_id', 'admin_id', 'user_id',
        'feature', 'provider', 'model',
        'prompt', 'response',
        'input_tokens', 'output_tokens',
        'cost_calls', 'cost_amount', 'duration_ms',
        'status', 'error_message', 'context',
        'created_at', 'updated_at',
    ];

    protected $casts = [
        'app_id'        => 'integer',
        'admin_id'      => 'integer',
        'user_id'       => 'integer',
        'input_tokens'  => 'integer',
        'output_tokens' => 'integer',
        'cost_calls'    => 'integer',
        'cost_amount'   => 'decimal:4',
        'duration_ms'   => 'integer',
        'status'        => 'integer',
        'context'       => 'array',
        'created_at'    => 'integer',
        'updated_at'    => 'integer',
    ];

    // 功能类型常量
    const FEATURE_COPYWRITING      = 'copywriting';      // AI 商品文案生成
    const FEATURE_CUSTOMER_SERVICE = 'customer_service';  // AI 智能客服
    const FEATURE_ANALYTICS        = 'analytics';         // AI 数据分析

    // 状态常量
    const STATUS_FAILED  = 0;
    const STATUS_SUCCESS = 1;

    /**
     * 关联应用
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /**
     * 关联管理员
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }

    /**
     * 获取功能类型文本
     */
    public function getFeatureTextAttribute(): string
    {
        $map = [
            self::FEATURE_COPYWRITING      => '商品文案生成',
            self::FEATURE_CUSTOMER_SERVICE => '智能客服',
            self::FEATURE_ANALYTICS        => '数据分析',
        ];
        return $map[$this->feature] ?? $this->feature;
    }
}
