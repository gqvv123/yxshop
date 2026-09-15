<?php

namespace app\service;

use app\repository\UserLevelRepository;
use app\exception\BusinessException;
use Exception;

/**
 * 用户等级服务类
 *
 * @property UserLevelRepository $repository
 */
class UserLevelService extends BaseService
{
    public function __construct(?UserLevelRepository $repository = null)
    {
        parent::__construct($repository ?? new UserLevelRepository());
    }

    /**
     * 获取等级列表
     */
    public function getLevelList($appId = 0)
    {
        try {
            $this->logInfo('获取等级列表开始', ['app_id' => $appId]);

            $levels = $this->repository->getLevels($appId);

            $this->logInfo('获取等级列表成功', ['app_id' => $appId]);
            return $levels;

        } catch (Exception $e) {
            $this->logError('获取等级列表失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 创建等级
     */
    public function createLevel(array $data)
    {
        try {
            $this->logInfo('创建等级开始', ['data' => $data]);


            // 检查等级是否重复
            $existing = $this->repository->getByLevel($data['level'], $data['app_id']);
            if ($existing) {
                throw new BusinessException('等级已存在');
            }

            // 如果是默认等级，先取消其他默认等级
            if (!empty($data['is_default'])) {
                $this->repository->clearDefaultForApp((int) $data['app_id']);
            }

            $level = $this->repository->create($data);

            $this->logInfo('创建等级成功', ['level_id' => $level->id]);
            return $level;

        } catch (Exception $e) {
            $this->logError('创建等级失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新等级
     */
    public function updateLevel($id, array $data)
    {
        try {
            $this->logInfo('更新等级开始', ['id' => $id, 'data' => $data]);

            $level = $this->repository->findOrFail($id);

            // 检查等级是否重复
            if (isset($data['level'])) {
                $existing = $this->repository->getByLevel($data['level'], $level->app_id);
                if ($existing && $existing->id != $id) {
                    throw new BusinessException('等级已存在');
                }
            }

            // 如果是默认等级，先取消其他默认等级
            if (!empty($data['is_default'])) {
                $this->repository->clearDefaultForApp((int) $level->app_id, $id);
            }

            $level = $this->repository->update($id, $data);

            $this->logInfo('更新等级成功', ['level_id' => $id]);
            return $level;

        } catch (Exception $e) {
            $this->logError('更新等级失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 删除等级
     */
    public function deleteLevel($id)
    {
        try {
            $this->logInfo('删除等级开始', ['id' => $id]);

            $level = $this->repository->findOrFail($id);

            // 检查是否有用户使用该等级
            if ($level->users()->count() > 0) {
                throw new BusinessException('该等级有用户使用，无法删除');
            }

            $this->repository->delete($id);

            $this->logInfo('删除等级成功', ['level_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除等级失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取默认等级
     */
    public function getDefaultLevel($appId = 0)
    {
        try {
            return $this->repository->getDefaultLevel($appId);

        } catch (Exception $e) {
            $this->logError('获取默认等级失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 根据等级值获取等级
     */
    public function getByLevel($level, $appId = 0)
    {
        try {
            return $this->repository->getByLevel($level, $appId);

        } catch (Exception $e) {
            $this->logError('根据等级值获取等级失败', [
                'level' => $level,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取等级统计
     */
    public function getLevelStats($appId = 0)
    {
        try {
            return $this->repository->getLevelStats($appId);

        } catch (Exception $e) {
            $this->logError('获取等级统计失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        return $this->repository->paginateForList((int) $appId, (int) $pageSize);
    }

    public function updateStatus($id, int $status): bool
    {
        $this->repository->update($id, ['status' => $status]);
        return true;
    }
}
