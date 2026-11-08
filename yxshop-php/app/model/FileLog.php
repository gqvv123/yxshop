<?php

namespace app\model;

/**
 * 文件日志模型
 */
class FileLog extends BaseModel
{
    protected $table = 'yxshop_file_logs';

    protected $fillable = [
        'file_id',
        'file_name',
        'file_path',
        'file_url',
        'file_size',
        'operation_type',
        'operation_result',
        'operation_data',
        'user_id',
        'app_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'file_id' => 'integer',
        'file_size' => 'integer',
        'operation_type' => 'integer',
        'operation_result' => 'array',
        'operation_data' => 'array',
        'user_id' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    // 操作类型常量
    const TYPE_UPLOAD = 1;        // 上传
    const TYPE_DOWNLOAD = 2;      // 下载
    const TYPE_DELETE = 3;        // 删除
    const TYPE_UPDATE = 4;        // 更新
    const TYPE_COPY = 5;          // 复制
    const TYPE_MOVE = 6;          // 移动

    /**
     * 获取操作类型文本
     */
    public function getOperationTypeTextAttribute()
    {
        $types = [
            self::TYPE_UPLOAD => '上传',
            self::TYPE_DOWNLOAD => '下载',
            self::TYPE_DELETE => '删除',
            self::TYPE_UPDATE => '更新',
            self::TYPE_COPY => '复制',
            self::TYPE_MOVE => '移动',
        ];

        return $types[$this->operation_type] ?? '未知';
    }

    /**
     * 关联文件
     */
    public function file()
    {
        return $this->belongsTo(UploadFile::class, 'file_id', 'id');
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 关联应用
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /**
     * 记录文件操作
     */
    public static function record($fileId, $fileName, $filePath, $fileUrl, $fileSize, $operationType, $operationResult = [], $operationData = [], $userId = 0, $appId = 0)
    {
        return static::create([
            'file_id' => $fileId,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_url' => $fileUrl,
            'file_size' => $fileSize,
            'operation_type' => $operationType,
            'operation_result' => $operationResult,
            'operation_data' => $operationData,
            'user_id' => $userId,
            'app_id' => $appId,
        ]);
    }
}
