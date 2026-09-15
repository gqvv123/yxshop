<?php

namespace app\model;

class FileDownloadLog extends BaseModel
{
    protected $table = 'yxshop_file_download_logs';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    protected $fillable = [
        'file_id', 'file_name', 'file_size',
        'user_id', 'user_name', 'ip', 'user_agent',
    ];

    protected $casts = [
        'file_id' => 'integer',
        'file_size' => 'integer',
        'user_id' => 'integer',
        'created_at' => 'integer',
    ];

    public function file()
    {
        return $this->belongsTo(UploadFile::class, 'file_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(Admin::class, 'user_id', 'id');
    }
}
