<?php

namespace app\service;

use app\repository\ParamTemplateRepository;
use app\model\ParamTemplate;
use Exception;

/**
 * 参数模板服务类
 *
 * @property ParamTemplateRepository $repository
 */
class ParamTemplateService extends BaseService
{
    public function __construct(?ParamTemplateRepository $repository = null)
    {
        parent::__construct($repository ?? new ParamTemplateRepository());
    }

    public function getEnabled(int $appId = 0)
    {
        try {
            return $this->repository->getEnabled($appId);
        } catch (Exception $e) {
            $this->logError('获取已启用模板失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByCategory(int $categoryId, int $appId = 0)
    {
        try {
            return $this->repository->getByCategory($categoryId, $appId);
        } catch (Exception $e) {
            $this->logError('按分类获取模板失败', [
                'category_id' => $categoryId,
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
            $this->logError('获取模板分页列表失败', [
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
            $this->logError('获取模板统计失败', [
                'app_id' => $conditions['app_id'] ?? 0,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function enable(int $id)
    {
        try {
            $this->logInfo('启用模板开始', ['id' => $id]);
            $template = $this->repository->findOrFail($id);
            $template->status = ParamTemplate::STATUS_ENABLED;
            $template->save();
            $this->logInfo('启用模板成功', ['id' => $id]);
            return $template;
        } catch (Exception $e) {
            $this->logError('启用模板失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function disable(int $id)
    {
        try {
            $this->logInfo('禁用模板开始', ['id' => $id]);
            $template = $this->repository->findOrFail($id);
            $template->status = ParamTemplate::STATUS_DISABLED;
            $template->save();
            $this->logInfo('禁用模板成功', ['id' => $id]);
            return $template;
        } catch (Exception $e) {
            $this->logError('禁用模板失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $this->logInfo('创建模板开始', ['data' => $data]);
            $template = $this->repository->create($data);
            $this->logInfo('创建模板成功', ['id' => $template->id]);
            return $template;
        } catch (Exception $e) {
            $this->logError('创建模板失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        try {
            $this->logInfo('更新模板开始', ['id' => $id, 'data' => $data]);
            $template = $this->repository->update($id, $data);
            $this->logInfo('更新模板成功', ['id' => $id]);
            return $template;
        } catch (Exception $e) {
            $this->logError('更新模板失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
