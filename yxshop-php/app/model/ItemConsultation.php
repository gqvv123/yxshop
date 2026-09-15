<?php

namespace app\model;

class ItemConsultation extends BaseModel
{
    protected $table = 'yxshop_item_consultations';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    const CONSULT_TYPE_ITEM = 1;
    const CONSULT_TYPE_LOGISTICS = 2;
    const CONSULT_TYPE_AFTER_SALES = 3;

    const STATUS_PENDING = 0;
    const STATUS_REPLIED = 1;

    protected $fillable = [
        'item_id', 'user_id', 'consult_type', 'content', 'reply_content',
        'replier_id', 'is_anonymous', 'status', 'app_id'
    ];

    protected $casts = [
        'item_id' => 'integer',
        'user_id' => 'integer',
        'consult_type' => 'integer',
        'replier_id' => 'integer',
        'is_anonymous' => 'integer',
        'status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

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