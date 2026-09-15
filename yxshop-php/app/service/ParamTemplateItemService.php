<?php

namespace app\service;

use app\repository\ParamTemplateItemRepository;
use Exception;

/**
 * 参数模板项服务类
 *
 * @property ParamTemplateItemRepository $repository
 */
class ParamTemplateItemService extends BaseService
{
    public function __construct(?ParamTemplateItemRepository $repository = null)
    {
        parent::__construct($repository ?? new ParamTemplateItemRepository());
    }

    public function getByTemplate(int $templateId, int $appId = 0)
    {
        try {
            return $this->repository->getByTemplate($templateId, $appId);
        } catch (Exception $e) {
            $this->logError('按模板获取参数项失败', [
                'template_id' => $templateId,
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
            $this->logError('获取参数项分页列表失败', [
                'app_id' => $appId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $this->logInfo('创建参数项开始', ['data' => $data]);
            $item = $this->repository->create($data);
            $this->logInfo('创建参数项成功', ['id' => $item->id]);
            return $item;
        } catch (Exception $e) {
            $this->logError('创建参数项失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        try {
            $this->logInfo('更新参数项开始', ['id' => $id, 'data' => $data]);
            $item = $this->repository->update($id, $data);
            $this->logInfo('更新参数项成功', ['id' => $id]);
            return $item;
        } catch (Exception $e) {
            $this->logError('更新参数项失败', [
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
            $this->logInfo('删除参数项开始', ['id' => $id]);
            $result = $this->repository->delete($id);
            $this->logInfo('删除参数项成功', ['id' => $id]);
            return $result;
        } catch (Exception $e) {
            $this->logError('删除参数项失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function batchCreate(int $templateId, array $items, int $appId = 0)
    {
        try {
            $this->logInfo('批量创建参数项开始', ['template_id' => $templateId, 'count' => count($items)]);
            $now = time();
            $insertData = [];
            foreach ($items as $item) {
                $insertData[] = [
                    'template_id' => $templateId,
                    'param_name' => $item['param_name'] ?? '',
                    'param_type' => $item['param_type'] ?? 'text',
                    'param_values' => $item['param_values'] ?? [],
                    'sort' => $item['sort'] ?? 0,
                    'is_required' => $item['is_required'] ?? false,
                    'app_id' => $appId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $result = $this->repository->createMany($insertData);
            $this->logInfo('批量创建参数项成功', ['template_id' => $templateId, 'count' => count($items)]);
            return $result;
        } catch (Exception $e) {
            $this->logError('批量创建参数项失败', [
                'template_id' => $templateId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
