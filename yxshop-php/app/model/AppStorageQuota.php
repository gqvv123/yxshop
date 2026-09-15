<?php

namespace app\model;

class AppStorageQuota extends BaseModel
{
    protected $table = 'yxshop_app_storage_quotas';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    protected $fillable = [
        'app_id', 'quota_bytes', 'warn_percent', 'used_bytes',
        'operator_id', 'operator_name',
    ];

    protected $casts = [
        'app_id' => 'integer',
        'quota_bytes' => 'integer',
        'warn_percent' => 'integer',
        'used_bytes' => 'integer',
        'operator_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /** 已用百分比 */
    public function usedPercent(): float
    {
        if ($this->quota_bytes <= 0) {
            return 0;
        }
        return round($this->used_bytes / $this->quota_bytes * 100, 2);
    }

    /** 是否达到预警阈值 */
    public function isWarning(): bool
    {
        return $this->usedPercent() >= $this->warn_percent;
    }

    /** 剩余可用字节数 */
    public function remainingBytes(): int
    {
        return max(0, $this->quota_bytes - $this->used_bytes);
    }
}
