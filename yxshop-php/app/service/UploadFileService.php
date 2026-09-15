<?php

namespace app\service;

use app\repository\UploadFileRepository;
use app\repository\AppStorageQuotaRepository;
use app\repository\FileDownloadLogRepository;
use app\model\UploadFile;
use app\model\AppStorageQuota;
use app\exception\BusinessException;
use support\Db;
use Exception;

/**
 * 上传文件服务
 */
class UploadFileService extends BaseService
{
    // 暴露审核状态常量供 Controller 层使用，避免 Controller 直接引用 Model
    const AUDIT_PENDING  = UploadFile::AUDIT_PENDING;
    const AUDIT_PASSED   = UploadFile::AUDIT_PASSED;
    const AUDIT_REJECTED = UploadFile::AUDIT_REJECTED;

    protected AppStorageQuotaRepository $quotaRepository;
    protected FileDownloadLogRepository $logRepo;

    public function __construct(UploadFileRepository $repository = null)
    {
        $repository = $repository ?? new UploadFileRepository();
        parent::__construct($repository);
        $this->quotaRepository = new AppStorageQuotaRepository();
        $this->logRepo = new FileDownloadLogRepository();
    }

    public function getFileList($page = 1, $limit = 20, $filters = [])
    {
        return $this->repository->getFiles($page, $limit, $filters);
    }

    /**
     * 获取文件详情（含下载日志）
     */
    public function getFileById($id)
    {
        try {
            return $this->repository->getDetail((int) $id);
        } catch (Exception $e) {
            $this->logError('获取文件详情失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 创建文件记录（检查配额）
     */
    public function createFile(array $data)
    {
        try {
            $this->logInfo('创建文件记录开始', ['data' => $data]);

            return Db::transaction(function () use ($data) {
                $appId = $data['app_id'] ?? 0;
                $fileSize = $data['file_size'] ?? 0;

                // 检查存储配额（业务逻辑在 Service 层）
                $quota = $this->quotaRepository->getByAppId($appId);
                if ($quota) {
                    if ($quota->remainingBytes() < $fileSize) {
                        throw new BusinessException('存储配额不足，当前剩余 ' . round($quota->remainingBytes() / 1048576, 2) . ' MB', 507);
                    }
                }

                $file = $this->repository->createFile($data);

                // 更新已用额度
                if ($quota) {
                    $this->quotaRepository->increaseUsedBytes($appId, $fileSize);
                }

                $this->logInfo('创建文件记录成功', ['id' => $file->id]);
                return $file;
            });
        } catch (BusinessException $e) {
            $this->logWarning('创建文件记录业务异常', ['error' => $e->getMessage()]);
            throw $e;
        } catch (Exception $e) {
            $this->logError('创建文件记录失败', ['data' => $data, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 删除文件（事务 + 配额清理 + 物理文件删除）
     */
    public function deleteFile($fileId)
    {
        try {
            $this->logInfo('删除文件开始', ['id' => $fileId]);

            $result = Db::transaction(function () use ($fileId) {
                $file = $this->repository->find($fileId);
                if (!$file) {
                    return false;
                }

                // 减少配额占用
                $this->quotaRepository->decreaseUsedBytes($file->app_id, $file->file_size);

                // 删除物理文件
                $filePath = public_path($file->file_path ?: $file->file_name);
                if ($filePath && file_exists($filePath)) {
                    @unlink($filePath);
                }

                return (bool) $file->delete();
            });

            $this->logInfo('删除文件成功', ['id' => $fileId]);
            return $result;
        } catch (Exception $e) {
            $this->logError('删除文件失败', ['id' => $fileId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function batchMoveToGroup(array $ids, int $groupId)
    {
        return $this->repository->batchMoveToGroup($ids, $groupId);
    }

    public function moveToGroup($fileId, $groupId)
    {
        return $this->repository->update($fileId, ['group_id' => $groupId]);
    }

    /**
     * 审核文件
     */
    public function audit(int $id, int $auditStatus, int $auditorId, ?string $remark = null): UploadFile
    {
        try {
            $this->logInfo('审核文件开始', ['id' => $id, 'audit_status' => $auditStatus]);

            // 业务逻辑：检查是否已审核
            $file = $this->repository->find($id);
            if (!$file) {
                throw new BusinessException('文件不存在');
            }
            if ($file->audit_status !== UploadFile::AUDIT_PENDING) {
                throw new BusinessException('该文件已审核，请勿重复操作');
            }

            $file = $this->repository->updateAudit($id, $auditStatus, $auditorId, $remark);
            $this->logInfo('审核文件成功', ['id' => $id]);
            return $file;
        } catch (BusinessException $e) {
            $this->logWarning('审核文件业务异常', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (Exception $e) {
            $this->logError('审核文件失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 记录下载日志
     */
    public function logDownload(int $fileId, int $userId, ?string $userName, string $ip, ?string $userAgent): void
    {
        try {
            $file = $this->repository->find($fileId);
            if (!$file) {
                return;
            }

            $this->logRepo->createLog($fileId, $file->original_name, $file->file_size, $userId, $userName, $ip, $userAgent);

            // 更新下载次数
            $file->download_count = ($file->download_count ?? 0) + 1;
            $file->save();
        } catch (Exception $e) {
            // 下载日志失败不影响下载，吞掉
            $this->logError('记录下载日志失败', ['file_id' => $fileId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * 获取应用存储配额
     */
    public function getQuota(int $appId)
    {
        return $this->quotaRepository->getByAppId($appId);
    }

    /**
     * 更新应用存储配额
     */
    public function updateQuota(int $appId, int $quotaBytes, int $operatorId, ?string $operatorName): AppStorageQuota
    {
        return $this->quotaRepository->updateOrCreate($appId, [
            'quota_bytes' => $quotaBytes,
            'operator_id' => $operatorId,
            'operator_name' => $operatorName,
        ]);
    }

    public function getFileStats($appId = 0)
    {
        return $this->repository->getFileStats($appId);
    }
}
