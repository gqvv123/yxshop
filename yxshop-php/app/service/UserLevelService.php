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

            // 必填项兜底，避免空提交时出现 Undefined array key 的 500
            $data['level'] = max(1, (int) ($data['level'] ?? 1));
            $data['app_id'] = (int) ($data['app_id'] ?? 0);
            $name = trim((string) ($data['name'] ?? ''));
            $data['name'] = $name !== '' ? $name : ('V' . $data['level']);

            $data = $this->normalizeLevelData($data);


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

            $data  = $this->normalizeLevelData($data);
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
     * 统一等级入参：
     * - 兼容旧字段（desc / agio / integral）
     * - discount（10=不打折）与 agio 双写，保证订单折扣与前端展示一致
     * - 缺失字段给默认值，避免 NOT NULL 报错
     */
    private function normalizeLevelData(array $data): array
    {
        if (isset($data['desc']) && !isset($data['description'])) {
            $data['description'] = (string) $data['desc'];
        }
        unset($data['desc'], $data['sort'], $data['integral'], $data['values'], $data['key'], $data['shop_money']);

        if (isset($data['discount'])) {
            $data['discount'] = round((float) $data['discount'], 2);
            $data['agio'] = $data['discount'];
        } elseif (isset($data['agio'])) {
            $data['agio'] = round((float) $data['agio'], 2);
            $data['discount'] = $data['agio'];
        }

        if (isset($data['level'])) {
            $data['level'] = max(1, (int) $data['level']);
        }
        if (isset($data['experience'])) {
            $data['experience'] = max(0, (int) $data['experience']);
        }
        if (isset($data['status'])) {
            $data['status'] = (int) $data['status'] === 0 ? 0 : 1;
        }
        if (array_key_exists('is_default', $data)) {
            $data['is_default'] = !empty($data['is_default']) ? 1 : 0;
        }
        if (isset($data['app_id'])) {
            $data['app_id'] = (int) $data['app_id'];
        }

        return $data;
    }

    /**
     * 删除等级
     */
    public function deleteLevel($id): bool
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
