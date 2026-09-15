<?php

namespace app\service;

use app\repository\PromItemRepository;
use app\model\PromItem;
use app\exception\BusinessException;
use Exception;

/**
 * 促销商品服务类
 *
 * @property PromItemRepository $repository
 */
class PromItemService extends BaseService
{
    public function __construct(?PromItemRepository $repository = null)
    {
        parent::__construct($repository ?? new PromItemRepository());
    }

    public function getPromItemList($promId = null, $appId = 0)
    {
        try {
            $this->logInfo('获取促销商品列表开始', ['prom_id' => $promId, 'app_id' => $appId]);

            $promItems = $this->repository->getPromItems($promId, $appId);

            $this->logInfo('获取促销商品列表成功', ['app_id' => $appId]);
            return $promItems;

        } catch (Exception $e) {
            $this->logError('获取促销商品列表失败', [
                'prom_id' => $promId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getAvailablePromItems($promId = null, $appId = 0)
    {
        try {
            return $this->repository->getAvailablePromItems($promId, $appId);

        } catch (Exception $e) {
            $this->logError('获取可用促销商品失败', [
                'prom_id' => $promId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function createPromItem(array $data)
    {
        try {
            $this->logInfo('创建促销商品开始', ['data' => $data]);

            $promItem = $this->repository->create($data);

            $this->logInfo('创建促销商品成功', ['id' => $promItem->id]);
            return $promItem;

        } catch (Exception $e) {
            $this->logError('创建促销商品失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updatePromItem($id, array $data)
    {
        try {
            $this->logInfo('更新促销商品开始', ['id' => $id, 'data' => $data]);

            $promItem = $this->repository->findOrFail($id);

            $promItem = $this->repository->update($id, $data);

            $this->logInfo('更新促销商品成功', ['id' => $id]);
            return $promItem;

        } catch (Exception $e) {
            $this->logError('更新促销商品失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function batchDelete(array $ids)
    {
        try {
            $this->logInfo('批量删除促销商品开始', ['ids' => $ids]);
            $this->repository->deleteWhere(['id' => $ids]);
            $this->logInfo('批量删除促销商品成功', ['ids' => $ids]);
            return true;
        } catch (Exception $e) {
            $this->logError('批量删除促销商品失败', ['ids' => $ids, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deletePromItem($id)
    {
        try {
            $this->logInfo('删除促销商品开始', ['id' => $id]);

            $this->repository->delete($id);

            $this->logInfo('删除促销商品成功', ['id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除促销商品失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getPromItemStats($promId = null, $appId = 0)
    {
        try {
            return $this->repository->getPromItemStats($promId, $appId);

        } catch (Exception $e) {
            $this->logError('获取促销商品统计失败', [
                'prom_id' => $promId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getPromByItemId($itemId, $appId = 0)
    {
        try {
            return $this->repository->getPromByItemId($itemId, $appId);

        } catch (Exception $e) {
            $this->logError('根据商品ID获取促销信息失败', [
                'item_id' => $itemId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updatePromStock($id, $quantity)
    {
        try {
            $this->logInfo('更新促销商品库存开始', ['id' => $id, 'quantity' => $quantity]);

            $promItem = $this->repository->findOrFail($id);

            $newStock = $promItem->prom_stock - $quantity;
            if ($newStock < 0) {
                throw new BusinessException('库存不足');
            }

            $promItem->prom_stock = $newStock;
            $promItem->prom_sales += $quantity;

            if ($newStock == 0) {
                $promItem->prom_status = PromItem::STATUS_SOLD_OUT;
            }

            $promItem->save();

            $this->logInfo('更新促销商品库存成功', ['id' => $id, 'new_stock' => $newStock]);
            return $promItem;

        } catch (Exception $e) {
            $this->logError('更新促销商品库存失败', [
                'id' => $id,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        return $this->repository->paginatedForAdmin((int) $appId, (int) $pageSize, (string) $keyword);
    }
}