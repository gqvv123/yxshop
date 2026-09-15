<?php

namespace app\service;

use app\repository\NotificationSceneRepository;
use app\exception\BusinessException;
use Exception;

class NotificationSceneService extends BaseService
{
    public function __construct(?NotificationSceneRepository $repository = null)
    {
        parent::__construct($repository ?? new NotificationSceneRepository());
    }

    public function getSceneList($appId = 0)
    {
        try {
            $this->logInfo('获取通知场景列表开始', ['app_id' => $appId]);

            $scenes = $this->repository->listByAppSorted((int) $appId);

            $this->logInfo('获取通知场景列表成功', ['app_id' => $appId]);
            return $scenes;

        } catch (Exception $e) {
            $this->logError('获取通知场景列表失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getSceneByCode($code)
    {
        try {
            $this->logInfo('根据编码获取通知场景开始', ['code' => $code]);

            $scene = $this->repository->findByCode($code);

            $this->logInfo('根据编码获取通知场景成功', ['code' => $code]);
            return $scene;

        } catch (Exception $e) {
            $this->logError('根据编码获取通知场景失败', [
                'code' => $code,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function createScene(array $data)
    {
        try {
            $this->logInfo('创建通知场景开始', ['data' => $data]);

            $existing = $this->repository->findByCodeAndApp($data['scene_code'], $data['app_id']);

            if ($existing) {
                throw new BusinessException('场景编码已存在');
            }

            $scene = $this->repository->create($data);

            $this->logInfo('创建通知场景成功', ['scene_id' => $scene->id]);
            return $scene;

        } catch (Exception $e) {
            $this->logError('创建通知场景失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updateScene($id, array $data)
    {
        try {
            $this->logInfo('更新通知场景开始', ['id' => $id, 'data' => $data]);

            $scene = $this->repository->findOrFail($id);

            if (isset($data['scene_code'])) {
                $existing = $this->repository->findByCodeAndAppExcept($data['scene_code'], $scene->app_id, $id);

                if ($existing) {
                    throw new BusinessException('场景编码已存在');
                }
            }

            $scene = $this->repository->update($id, $data);

            $this->logInfo('更新通知场景成功', ['scene_id' => $id]);
            return $scene;

        } catch (Exception $e) {
            $this->logError('更新通知场景失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deleteScene($id)
    {
        try {
            $this->logInfo('删除通知场景开始', ['id' => $id]);

            $this->repository->findOrFail($id);
            $this->repository->delete($id);

            $this->logInfo('删除通知场景成功', ['scene_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除通知场景失败', [
                'id' => $id,
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
