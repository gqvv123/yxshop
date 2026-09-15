<?php

namespace app\model;

class UserTag extends BaseModel
{
    protected $table = 'yxshop_user_tags';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    const TYPE_MANUAL  = 'manual';
    const TYPE_AUTO    = 'auto';
    const TYPE_SYSTEM  = 'system';

    protected $fillable = [
        'name', 'color', 'tag_type', 'description',
        'sort', 'status', 'app_id',
    ];

    protected $casts = [
        'sort' => 'integer',
        'status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'yxshop_user_tag_relations', 'tag_id', 'user_id');
    }

    public function relations()
    {
        return $this->hasMany(UserTagRelation::class, 'tag_id', 'id');
    }
}
