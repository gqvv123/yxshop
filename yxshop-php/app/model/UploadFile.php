<?php

namespace app\model;

class UploadFile extends BaseModel
{
    protected $table = 'yxshop_upload_files';

    protected $fillable = [
        'storage',
        'original_name',
        'group_id',
        'file_url',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
        'file_ext',
        'is_user',
        'deleted_at',
        'app_id',
        'user_id',
        'audit_status',
        'is_sensitive',
        'audit_remark',
        'auditor_id',
        'audited_at',
        'download_count',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'group_id' => 'integer',
        'user_id' => 'integer',
        'app_id' => 'integer',
        'is_user' => 'integer',
        'deleted_at' => 'integer',
        'audit_status' => 'integer',
        'is_sensitive' => 'integer',
        'auditor_id' => 'integer',
        'audited_at' => 'integer',
        'download_count' => 'integer',
    ];

    // 审核状态
    const AUDIT_PENDING  = 0;  // 待审核
    const AUDIT_PASSED   = 1;  // 通过
    const AUDIT_REJECTED = 2;  // 违规

    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_AUDIO = 'audio';
    const TYPE_DOCUMENT = 'document';
    const TYPE_ARCHIVE = 'archive';
    const TYPE_OTHER = 'other';

    public function group()
    {
        return $this->belongsTo(UploadGroup::class, 'group_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function downloadLogs()
    {
        return $this->hasMany(FileDownloadLog::class, 'file_id', 'id')
            ->orderBy('created_at', 'desc');
    }

    public function auditor()
    {
        return $this->belongsTo(Admin::class, 'auditor_id', 'id');
    }

    public function getAuditStatusText(): string
    {
        return [
            self::AUDIT_PENDING  => '待审核',
            self::AUDIT_PASSED   => '通过',
            self::AUDIT_REJECTED => '违规',
        ][$this->audit_status] ?? '未知';
    }
}
