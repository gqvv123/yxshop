<?php

namespace app\service;

use app\repository\AppPrepayIdRepository;
use app\exception\BusinessException;
use Exception;

/**
 * 应用服务类
 *
 * @property AppPrepayIdRepository $repository
 */
class AppPrepayIdService extends BaseService
{
    public function __construct(AppPrepayIdRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * 获取应用列表
     */
    public function getAppList($userId = null)
    {
        try {
            $this->logInfo('获取应用列表开始', ['user_id' => $userId]);

            $apps = $this->repository->getApps($userId);

            $this->logInfo('获取应用列表成功', ['user_id' => $userId]);
            return $apps;

        } catch (Exception $e) {
            $this->logError('获取应用列表失败', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 创建应用
     */
    public function createApp(array $data)
    {
        try {
            $this->logInfo('创建应用开始', ['data' => $data]);


            // 检查AppKey是否重复
            $existing = $this->repository->findByAppKey($data['appkey']);
            if ($existing) {
                throw new BusinessException('AppKey已存在');
            }

            $app = $this->repository->create($data);

            $this->logInfo('创建应用成功', ['app_id' => $app->app_id]);
            return $app;

        } catch (Exception $e) {
            $this->logError('创建应用失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新应用
     */
    public function updateApp($id, array $data)
    {
        try {
            $this->logInfo('更新应用开始', ['id' => $id, 'data' => $data]);


            $app = $this->repository->findOrFail($id);

            // 检查AppKey是否重复
            if (isset($data['appkey'])) {
                $existing = $this->repository->findByAppKey($data['appkey']);
                if ($existing && $existing->app_id != $id) {
                    throw new BusinessException('AppKey已存在');
                }
            }

            $app = $this->repository->update($id, $data);

            $this->logInfo('更新应用成功', ['app_id' => $id]);
            return $app;

        } catch (Exception $e) {
            $this->logError('更新应用失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 删除应用
     */
    public function deleteApp($id)
    {
        try {
            $this->logInfo('删除应用开始', ['id' => $id]);

            $app = $this->repository->findOrFail($id);

            // 软删除
            $app->deleted_at = time();
            $app->save();

            $this->logInfo('删除应用成功', ['app_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除应用失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 根据AppKey获取应用
     */
    public function getAppByKey($appKey)
    {
        try {
            return $this->repository->findByAppKey($appKey);

        } catch (Exception $e) {
            $this->logError('根据AppKey获取应用失败', [
                'app_key' => $appKey,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 搜索应用
     */
    public function searchApps($keyword)
    {
        try {
            return $this->repository->searchApps($keyword);

        } catch (Exception $e) {
            $this->logError('搜索应用失败', [
                'keyword' => $keyword,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取应用统计
     */
    public function getAppStats()
    {
        try {
            return $this->repository->getAppStats();

        } catch (Exception $e) {
            $this->logError('获取应用统计失败', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
