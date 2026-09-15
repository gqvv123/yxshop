<?php

namespace app\model;

class UserTagRelation extends BaseModel
{
    protected $table = 'yxshop_user_tag_relations';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    protected $fillable = [
        'user_id', 'tag_id', 'operator_id', 'operator_name', 'reason', 'app_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'tag_id' => 'integer',
        'operator_id' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function tag()
    {
        return $this->belongsTo(UserTag::class, 'tag_id', 'id');
    }
}
