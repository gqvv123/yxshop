<?php

namespace app\repository;

use app\model\FileDownloadLog;

/**
 * 文件下载日志仓储
 */
class FileDownloadLogRepository extends BaseRepository
{
    protected $model = FileDownloadLog::class;

    /**
     * 记录下载日志
     */
    public function createLog(int $fileId, string $fileName, int $fileSize, int $userId, ?string $userName, string $ip, ?string $userAgent): FileDownloadLog
    {
        return $this->create([
            'file_id'    => $fileId,
            'file_name'  => $fileName,
            'file_size'  => $fileSize,
            'user_id'    => $userId,
            'user_name'  => $userName,
            'ip'         => $ip,
            'user_agent' => mb_substr($userAgent, 0, 512),
        ]);
    }
}
