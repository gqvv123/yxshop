<?php

namespace app\service;

use app\repository\ItemParamValueRepository;
use Exception;

/**
 * 商品参数值服务类
 *
 * @property ItemParamValueRepository $repository
 */
class ItemParamValueService extends BaseService
{
    public function __construct(?ItemParamValueRepository $repository = null)
    {
        parent::__construct($repository ?? new ItemParamValueRepository());
    }

    public function getByItem(int $itemId, int $appId = 0)
    {
        try {
            return $this->repository->getByItem($itemId, $appId);
        } catch (Exception $e) {
            $this->logError('按商品获取参数值失败', [
                'item_id' => $itemId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByTemplateItem(int $templateItemId, int $appId = 0)
    {
        try {
            return $this->repository->getByTemplateItem($templateItemId, $appId);
        } catch (Exception $e) {
            $this->logError('按模板项获取参数值失败', [
                'template_item_id' => $templateItemId,
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
            $this->logError('获取商品参数值分页列表失败', [
                'app_id' => $appId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function upsertByItem(int $itemId, array $paramValues, int $appId = 0)
    {
        try {
            $this->logInfo('批量更新商品参数值开始', ['item_id' => $itemId, 'count' => count($paramValues)]);
            $result = $this->repository->upsertByItem($itemId, $paramValues, $appId);
            $this->logInfo('批量更新商品参数值成功', ['item_id' => $itemId]);
            return $result;
        } catch (Exception $e) {
            $this->logError('批量更新商品参数值失败', [
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $this->logInfo('创建商品参数值开始', ['data' => $data]);
            $paramValue = $this->repository->create($data);
            $this->logInfo('创建商品参数值成功', ['id' => $paramValue->id]);
            return $paramValue;
        } catch (Exception $e) {
            $this->logError('创建商品参数值失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        try {
            $this->logInfo('更新商品参数值开始', ['id' => $id, 'data' => $data]);
            $paramValue = $this->repository->update($id, $data);
            $this->logInfo('更新商品参数值成功', ['id' => $id]);
            return $paramValue;
        } catch (Exception $e) {
            $this->logError('更新商品参数值失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
