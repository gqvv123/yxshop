<?php

namespace app\model;

use app\model\BaseModel;

class MiniPage extends BaseModel
{
    protected $table = 'yxshop_mini_pages';

    const TYPE_HOME = 10;
    const TYPE_CUSTOM = 20;

    const STATUS_DRAFT = 10;
    const STATUS_PUBLISHED = 20;
    const STATUS_OFFLINE = 30;

    const TYPE_MAP = [
        'home' => self::TYPE_HOME,
        'custom' => self::TYPE_CUSTOM,
    ];

    const STATUS_MAP = [
        'draft' => self::STATUS_DRAFT,
        'published' => self::STATUS_PUBLISHED,
        'offline' => self::STATUS_OFFLINE,
    ];

    protected $fillable = [
        'page_name', 'page_type', 'page_data', 'status',
        'theme_id', 'version', 'app_id',
    ];

    protected $casts = [
        'page_data' => 'array',
        'version' => 'integer',
        'app_id' => 'integer',
        'deleted_at' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    public static function normalizeType($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        return self::TYPE_MAP[$value] ?? null;
    }

    public static function normalizeStatus($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        return self::STATUS_MAP[$value] ?? null;
    }

    public function getPageTypeAttribute($value)
    {
        return array_search((int) $value, self::TYPE_MAP, true) ?: (int) $value;
    }

    public function setPageTypeAttribute($value)
    {
        $this->attributes['page_type'] = self::normalizeType($value) ?? self::TYPE_CUSTOM;
    }

    public function getStatusAttribute($value)
    {
        return array_search((int) $value, self::STATUS_MAP, true) ?: (int) $value;
    }

    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = self::normalizeStatus($value) ?? self::STATUS_DRAFT;
    }

    public function isHome(): bool
    {
        return (int) ($this->attributes['page_type'] ?? 0) === self::TYPE_HOME;
    }

    public function isPublished(): bool
    {
        return (int) ($this->attributes['status'] ?? 0) === self::STATUS_PUBLISHED;
    }

    public function theme()
    {
        return $this->belongsTo(MiniTheme::class, 'theme_id', 'id');
    }

    public function versions()
    {
        return $this->hasMany(MiniPageVersion::class, 'page_id', 'id');
    }
}