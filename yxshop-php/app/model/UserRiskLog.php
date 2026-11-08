<?php

namespace app\model;

class UserRiskLog extends BaseModel
{
    protected $table = 'yxshop_user_risk_logs';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    // 事件类型
    const EVENT_LOGIN_FAIL      = 'login_fail';
    const EVENT_MULTI_LOGIN     = 'multi_login';
    const EVENT_HIGH_FREQ       = 'high_freq';
    const EVENT_CHARGEBACK      = 'chargeback';
    const EVENT_SUSPICIOUS_ORDER = 'suspicious_order';
    const EVENT_FLAGGED         = 'flagged';

    // 风险等级
    const LEVEL_LOW    = 1;
    const LEVEL_MEDIUM = 2;
    const LEVEL_HIGH   = 3;

    protected $fillable = [
        'user_id', 'event_type', 'level', 'ip', 'device',
        'content', 'evidence', 'operator_id', 'handle_remark',
        'handled_at', 'app_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'level' => 'integer',
        'operator_id' => 'integer',
        'handled_at' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getLevelTextAttribute(): string
    {
        return [self::LEVEL_LOW => '低', self::LEVEL_MEDIUM => '中', self::LEVEL_HIGH => '高'][$this->level] ?? '未知';
    }
}
