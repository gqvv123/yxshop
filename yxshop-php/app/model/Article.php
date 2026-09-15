<?php

namespace app\model;

use app\model\BaseModel as Model;

/**
 * 文章模型
 */
class Article extends Model
{
    protected $table = 'yxshop_articles';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'title',
        'subtitle',
        'content',
        'excerpt',
        'cover_image',
        'category_id',
        'author',
        'status',
        'is_top',
        'is_recommend',
        'sort',
        'views',
        'likes',
        'app_id',
        'version',
        'deleted_at',
        'published_at',
        'scheduled_at',
        'change_summary',
        'modifier_id',
        'modifier_name',
    ];

    protected $softDeleteEnabled = true;

    protected $casts = [
        'category_id' => 'integer',
        'status' => 'integer',
        'is_top' => 'integer',
        'is_recommend' => 'integer',
        'sort' => 'integer',
        'views' => 'integer',
        'likes' => 'integer',
        'app_id' => 'integer',
        'version' => 'integer',
        'deleted_at' => 'integer',
        'published_at' => 'integer',
        'scheduled_at' => 'integer',
        'modifier_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    // 状态常量
    const STATUS_DRAFT     = 0;  // 草稿
    const STATUS_PUBLISHED = 1;  // 已发布
    const STATUS_HIDDEN    = 2;  // 隐藏
    const STATUS_SCHEDULED = 3;  // 待发布（定时）

    // 状态流转
    const STATUS_TRANSITIONS = [
        self::STATUS_DRAFT     => [self::STATUS_PUBLISHED, self::STATUS_HIDDEN, self::STATUS_SCHEDULED],
        self::STATUS_SCHEDULED => [self::STATUS_PUBLISHED, self::STATUS_HIDDEN, self::STATUS_DRAFT],
        self::STATUS_PUBLISHED => [self::STATUS_HIDDEN, self::STATUS_DRAFT],
        self::STATUS_HIDDEN    => [self::STATUS_PUBLISHED, self::STATUS_DRAFT],
    ];

    // 操作日志动作
    const ACTION_CREATE    = 'create';
    const ACTION_UPDATE    = 'update';
    const ACTION_PUBLISH   = 'publish';
    const ACTION_UNPUBLISH = 'unpublish';
    const ACTION_DELETE    = 'delete';

    public function category()
    {
        return $this->belongsTo(ArticleCategory::class, 'category_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    public function versions()
    {
        return $this->hasMany(ArticleVersion::class, 'article_id', 'id')
            ->orderBy('version', 'desc');
    }

    public function logs()
    {
        return $this->hasMany(ArticleLog::class, 'article_id', 'id')
            ->orderBy('created_at', 'desc');
    }

    public function isPublished(): bool
    {
        return (int) $this->status === self::STATUS_PUBLISHED;
    }

    public function isScheduled(): bool
    {
        return (int) $this->status === self::STATUS_SCHEDULED;
    }

    /** 是否已超时但未发布 */
    public function isOverdueScheduled(): bool
    {
        return $this->isScheduled()
            && $this->scheduled_at
            && time() > $this->scheduled_at;
    }

    public function canTransitionTo(int $targetStatus): bool
    {
        $allowed = self::STATUS_TRANSITIONS[$this->status] ?? [];
        return in_array($targetStatus, $allowed, true);
    }

    public function getStatusTextAttribute(): string
    {
        return [
            self::STATUS_DRAFT     => '草稿',
            self::STATUS_PUBLISHED => '已发布',
            self::STATUS_HIDDEN    => '隐藏',
            self::STATUS_SCHEDULED => '待发布',
        ][$this->status] ?? '未知';
    }

    public function incrementViews()
    {
        $this->increment('views');
    }

    public function incrementLikes()
    {
        $this->increment('likes');
    }
}
