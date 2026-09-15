<?php

namespace app\model;

class UploadGroup extends BaseModel
{
    protected $table = 'yxshop_upload_groups';
    protected $primaryKey = 'id';

    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'group_type',
        'group_name',
        'sort',
        'app_id',
    ];

    protected $casts = [
        'sort' => 'integer',
        'app_id' => 'integer',
    ];

    public function files()
    {
        return $this->hasMany(UploadFile::class, 'group_id', 'id');
    }
}
