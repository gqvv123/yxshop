<?php

namespace app\service;

use app\repository\NotificationBlacklistRepository;
use app\exception\BusinessException;
use Exception;

class NotificationBlacklistService extends BaseService
{
    public function __construct(?NotificationBlacklistRepository $repository = null)
    {
        parent::__construct($repository ?? new NotificationBlacklistRepository());
    }

    public function getBlacklist($appId = 0, $page = 1, $pageSize = 20)
    {
        try {
            $this->logInfo('获取通知黑名单列表开始', ['app_id' => $appId]);

            $list = $this->repository->paginateByApp((int) $appId, (int) $pageSize, (int) $page);

            $this->logInfo('获取通知黑名单列表成功', ['app_id' => $appId]);
            return $list;

        } catch (Exception $e) {
            $this->logError('获取通知黑名单列表失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function addToBlacklist(array $data)
    {
        try {
            $this->logInfo('添加通知黑名单开始', ['data' => $data]);

            $existing = $this->repository->findByUserSceneApp(
                $data['user_id'],
                $data['scene_code'],
                $data['app_id']
            );

            if ($existing) {
                throw new BusinessException('该用户已在黑名单中');
            }

            $record = $this->repository->create($data);

            $this->logInfo('添加通知黑名单成功', ['id' => $record->id]);
            return $record;

        } catch (Exception $e) {
            $this->logError('添加通知黑名单失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function removeFromBlacklist($id)
    {
        try {
            $this->logInfo('移除通知黑名单开始', ['id' => $id]);

            $this->repository->findOrFail($id);
            $this->repository->delete($id);

            $this->logInfo('移除通知黑名单成功', ['id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('移除通知黑名单失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function isBlacklisted($userId, $sceneCode)
    {
        try {
            return $this->repository->existsByUserAndScene($userId, $sceneCode);

        } catch (Exception $e) {
            $this->logError('检查黑名单状态失败', [
                'user_id' => $userId,
                'scene_code' => $sceneCode,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getBlacklistStats($appId)
    {
        try {
            $this->logInfo('获取通知黑名单统计开始', ['app_id' => $appId]);

            $stats = $this->repository->statsBySceneCode((int) $appId);

            $this->logInfo('获取通知黑名单统计成功', ['app_id' => $appId]);
            return $stats;

        } catch (Exception $e) {
            $this->logError('获取通知黑名单统计失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        return $this->repository->paginatedByApp((int) $appId, (int) $pageSize, 'created_at', 'desc');
    }
}
