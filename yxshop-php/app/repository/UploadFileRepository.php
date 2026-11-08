<?php

namespace app\repository;

use app\model\UploadFile;

/**
 * 上传文件仓储
 *
 * 4 层架构：Repository 只负责数据访问，业务逻辑（配额检查、审核状态检查）在 Service 层
 */
class UploadFileRepository extends BaseRepository
{
    protected $model = UploadFile::class;

    /**
     * 获取文件列表（支持审核状态筛选）
     */
    public function getFiles(int $page = 1, int $limit = 20, array $filters = [])
    {
        $query = $this->query()
            ->orderBy('created_at', 'desc');

        if (!empty($filters['group_id'])) {
            $query->where('group_id', $filters['group_id']);
        }

        if (!empty($filters['file_type'])) {
            $query->where('file_type', $filters['file_type']);
        }

        if (!empty($filters['app_id'])) {
            $query->where('app_id', $filters['app_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['audit_status']) && $filters['audit_status'] !== '') {
            $query->where('audit_status', (int) $filters['audit_status']);
        }

        if (isset($filters['is_sensitive'])) {
            $query->where('is_sensitive', (int) $filters['is_sensitive']);
        }

        return $query->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * 获取文件详情（含下载日志）
     */
    public function getDetail(int $id): ?UploadFile
    {
        return $this->query()
            ->with(['group', 'user', 'auditor', 'downloadLogs'])
            ->find($id);
    }

    public function getFilesByType(string $fileType, int $appId = 0)
    {
        $query = $this->query()
            ->where('file_type', $fileType)
            ->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    public function findByPath(string $filePath): ?UploadFile
    {
        return $this->query()
            ->where('file_name', $filePath)
            ->first();
    }

    public function getFileStats(int $appId = 0): array
    {
        $query = $this->query();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total'     => $query->count(),
            'total_size'=> $query->sum('file_size'),
            'by_type'   => $query->selectRaw('file_type, COUNT(*) as count, SUM(file_size) as total_size')
                ->groupBy('file_type')
                ->get(),
        ];
    }

    /**
     * 创建文件记录（不含配额检查，配额检查由 Service 层负责）
     */
    public function createFile(array $data): UploadFile
    {
        $file = $this->create($data);
        return $file;
    }

    /**
     * 更新文件审核信息
     */
    public function updateAudit(int $id, int $auditStatus, int $auditorId, ?string $remark = null): UploadFile
    {
        $file = $this->findOrFail($id);
        $file->audit_status = $auditStatus;
        $file->auditor_id = $auditorId;
        $file->audited_at = time();
        if ($remark) {
            $file->audit_remark = $remark;
        }
        $file->save();
        return $file;
    }

    /**
     * 批量移动文件分组
     */
    public function batchMoveToGroup(array $ids, int $groupId): int
    {
        return $this->updateWhere(['id' => $ids], ['group_id' => $groupId]);
    }

    /**
     * 删除文件记录（纯数据操作，不含事务和物理文件删除）
     */
    public function deleteFile($fileId): bool
    {
        $file = $this->find($fileId);
        if (!$file) {
            return false;
        }
        return (bool) $file->delete();
    }
}
