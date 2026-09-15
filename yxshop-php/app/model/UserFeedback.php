<?php

namespace app\model;

class UserFeedback extends BaseModel
{
    protected $table = 'yxshop_user_feedbacks';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    const TYPE_SUGGESTION = 1;
    const TYPE_BUG = 2;
    const TYPE_COMPLAINT = 3;
    const TYPE_OTHER = 4;

    const STATUS_PENDING = 0;
    const STATUS_REPLIED = 1;
    const STATUS_CLOSED = 2;

    protected $fillable = [
        'user_id', 'feedback_type', 'content', 'contact', 'images',
        'reply_content', 'replier_id', 'status', 'app_id'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'feedback_type' => 'integer',
        'replier_id' => 'integer',
        'status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function replier()
    {
        return $this->belongsTo(Admin::class, 'replier_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}