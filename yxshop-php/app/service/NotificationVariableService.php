<?php

namespace app\service;

use app\repository\NotificationVariableRepository;
use Exception;

class NotificationVariableService extends BaseService
{
    public function __construct(?NotificationVariableRepository $repository = null)
    {
        parent::__construct($repository ?? new NotificationVariableRepository());
    }

    public function getVariableList($sceneId = 0)
    {
        try {
            $this->logInfo('获取通知变量列表开始', ['scene_id' => $sceneId]);

            $variables = $this->repository->listBySceneSorted((int) $sceneId);

            $this->logInfo('获取通知变量列表成功', ['scene_id' => $sceneId]);
            return $variables;

        } catch (Exception $e) {
            $this->logError('获取通知变量列表失败', [
                'scene_id' => $sceneId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function createVariable(array $data)
    {
        try {
            $this->logInfo('创建通知变量开始', ['data' => $data]);

            if (!empty($data['variable_code'])) {
                $exists = $this->repository->query()
                    ->where('variable_code', $data['variable_code'])
                    ->where('app_id', $data['app_id'] ?? 0)
                    ->exists();
                if ($exists) {
                    throw new Exception('变量代码已存在');
                }
            }

            $variable = $this->repository->create($data);

            $this->logInfo('创建通知变量成功', ['variable_id' => $variable->id]);
            return $variable;

        } catch (Exception $e) {
            $this->logError('创建通知变量失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updateVariable($id, array $data)
    {
        try {
            $this->logInfo('更新通知变量开始', ['id' => $id, 'data' => $data]);

            $this->repository->findOrFail($id);

            if (!empty($data['variable_code'])) {
                $exists = $this->repository->query()
                    ->where('variable_code', $data['variable_code'])
                    ->where('id', '!=', $id)
                    ->where('app_id', $data['app_id'] ?? 0)
                    ->exists();
                if ($exists) {
                    throw new Exception('变量代码已存在');
                }
            }

            $variable = $this->repository->update($id, $data);

            $this->logInfo('更新通知变量成功', ['variable_id' => $id]);
            return $variable;

        } catch (Exception $e) {
            $this->logError('更新通知变量失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deleteVariable($id)
    {
        try {
            $this->logInfo('删除通知变量开始', ['id' => $id]);

            $this->repository->findOrFail($id);
            $this->repository->delete($id);

            $this->logInfo('删除通知变量成功', ['variable_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除通知变量失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getVariablesBySceneCode($sceneCode)
    {
        try {
            $this->logInfo('根据场景编码获取变量列表开始', ['scene_code' => $sceneCode]);

            $variables = $this->repository->listBySceneCode((string) $sceneCode);

            $this->logInfo('根据场景编码获取变量列表成功', ['scene_code' => $sceneCode]);
            return $variables;

        } catch (Exception $e) {
            $this->logError('根据场景编码获取变量列表失败', [
                'scene_code' => $sceneCode,
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
