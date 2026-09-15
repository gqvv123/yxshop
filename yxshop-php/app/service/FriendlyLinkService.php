<?php

namespace app\service;

use app\repository\FriendlyLinkRepository;
use app\model\FriendlyLink;
use Exception;

/**
 * 友情链接服务类
 *
 * @property FriendlyLinkRepository $repository
 */
class FriendlyLinkService extends BaseService
{
    public function __construct(?FriendlyLinkRepository $repository = null)
    {
        parent::__construct($repository ?? new FriendlyLinkRepository());
    }

    public function getEnabled(int $appId = 0)
    {
        try {
            return $this->repository->getEnabled($appId);
        } catch (Exception $e) {
            $this->logError('获取已启用链接失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        try {
            return $this->repository->getPaginatedList($appId, $filters, $pageSize);
        } catch (Exception $e) {
            $this->logError('获取链接分页列表失败', [
                'app_id' => $appId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getStats(array $conditions = [])
    {
        try {
            return $this->repository->getStats($conditions);
        } catch (Exception $e) {
            $this->logError('获取链接统计失败', [
                'app_id' => $conditions['app_id'] ?? 0,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function enable(int $id)
    {
        try {
            $this->logInfo('启用链接开始', ['id' => $id]);
            $link = $this->repository->findOrFail($id);
            $link->status = FriendlyLink::STATUS_ENABLED;
            $link->save();
            $this->logInfo('启用链接成功', ['id' => $id]);
            return $link;
        } catch (Exception $e) {
            $this->logError('启用链接失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function disable(int $id)
    {
        try {
            $this->logInfo('禁用链接开始', ['id' => $id]);
            $link = $this->repository->findOrFail($id);
            $link->status = FriendlyLink::STATUS_DISABLED;
            $link->save();
            $this->logInfo('禁用链接成功', ['id' => $id]);
            return $link;
        } catch (Exception $e) {
            $this->logError('禁用链接失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $this->logInfo('创建链接开始', ['data' => $data]);
            $link = $this->repository->create($data);
            $this->logInfo('创建链接成功', ['id' => $link->id]);
            return $link;
        } catch (Exception $e) {
            $this->logError('创建链接失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        try {
            $this->logInfo('更新链接开始', ['id' => $id, 'data' => $data]);
            $link = $this->repository->update($id, $data);
            $this->logInfo('更新链接成功', ['id' => $id]);
            return $link;
        } catch (Exception $e) {
            $this->logError('更新链接失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function delete($id)
    {
        try {
            $this->logInfo('删除链接开始', ['id' => $id]);
            $result = $this->repository->delete($id);
            $this->logInfo('删除链接成功', ['id' => $id]);
            return $result;
        } catch (Exception $e) {
            $this->logError('删除链接失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
