<?php

namespace app\model;

class Comment extends BaseModel
{
    protected $table = 'yxshop_comments';

    protected $fillable = [
        'score', 'content', 'images', 'is_anonymous', 'reply_content',
        'reply_time', 'is_recommend', 'sort', 'status', 'user_id',
        'order_id', 'item_id', 'order_item_id', 'app_id', 'deleted_at'
    ];

    protected $casts = [
        'score' => 'integer',
        'images' => 'array',
        'is_anonymous' => 'integer',
        'reply_time' => 'integer',
        'is_recommend' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'user_id' => 'integer',
        'order_id' => 'integer',
        'item_id' => 'integer',
        'order_item_id' => 'integer',
        'app_id' => 'integer',
        'deleted_at' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    const STATUS_PENDING = 10;
    const STATUS_APPROVED = 20;
    const STATUS_REJECTED = 30;

    const SCORE_GOOD = 10;
    const SCORE_MEDIUM = 20;
    const SCORE_BAD = 30;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    public function getStatusTextAttribute()
    {
        $statusMap = [
            self::STATUS_PENDING => '待审核',
            self::STATUS_APPROVED => '已通过',
            self::STATUS_REJECTED => '已拒绝',
        ];
        return $statusMap[$this->status] ?? '未知';
    }

    public function isApproved()
    {
        return $this->status == self::STATUS_APPROVED;
    }

    public function approve()
    {
        $this->status = self::STATUS_APPROVED;
        $this->save();
    }

    public function reject($reason = '')
    {
        $this->status = self::STATUS_REJECTED;
        $this->reply_content = $reason;
        $this->reply_time = time();
        $this->save();
    }
}
