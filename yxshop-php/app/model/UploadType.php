<?php

namespace app\model;

/**
 * 上传类型模型
 */
class UploadType extends BaseModel
{
    protected $table = 'yxshop_upload_types';

    protected $fillable = [
        'type_name',
        'type_ext',
        'type_mime',
        'max_size',
        'is_show',
        'sort',
        'app_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'type_ext' => 'array',
        'type_mime' => 'array',
        'max_size' => 'integer',
        'is_show' => 'integer',
        'sort' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    /**
     * 关联文件
     */
    public function files()
    {
        return $this->hasMany(UploadFile::class, 'type_id', 'id');
    }

    /**
     * 关联应用
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /**
     * 检查文件扩展名是否允许
     */
    public function isAllowedExt($ext)
    {
        $ext = strtolower($ext);
        return in_array($ext, $this->type_ext);
    }

    /**
     * 检查文件MIME类型是否允许
     */
    public function isAllowedMime($mime)
    {
        return in_array($mime, $this->type_mime);
    }

    /**
     * 检查文件大小是否允许
     */
    public function isAllowedSize($size)
    {
        return $size <= $this->max_size;
    }
}
