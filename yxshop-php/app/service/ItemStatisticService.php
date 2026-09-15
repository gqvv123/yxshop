<?php

namespace app\service;

use app\repository\ItemStatisticRepository;
use Exception;

/**
 * 商品统计服务类
 *
 * @property ItemStatisticRepository $repository
 */
class ItemStatisticService extends BaseService
{
    public function __construct(?ItemStatisticRepository $repository = null)
    {
        parent::__construct($repository ?? new ItemStatisticRepository());
    }

    public function getByItem(int $itemId, int $appId = 0)
    {
        try {
            return $this->repository->getByItem($itemId, $appId);
        } catch (Exception $e) {
            $this->logError('按商品获取统计失败', [
                'item_id' => $itemId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByDate(int $statDate, int $appId = 0)
    {
        try {
            return $this->repository->getByDate($statDate, $appId);
        } catch (Exception $e) {
            $this->logError('按日期获取统计失败', [
                'stat_date' => $statDate,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getHotItems(int $statDate, int $limit = 10, int $appId = 0)
    {
        try {
            return $this->repository->getHotItems($statDate, $limit, $appId);
        } catch (Exception $e) {
            $this->logError('获取热门商品失败', [
                'stat_date' => $statDate,
                'limit' => $limit,
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
            $this->logError('获取商品统计分页列表失败', [
                'app_id' => $appId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getItemSummary(int $itemId, int $appId = 0): array
    {
        try {
            return $this->repository->getItemSummary($itemId, $appId);
        } catch (Exception $e) {
            $this->logError('获取商品汇总统计失败', [
                'item_id' => $itemId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $this->logInfo('创建商品统计开始', ['data' => $data]);
            $stat = $this->repository->create($data);
            $this->logInfo('创建商品统计成功', ['id' => $stat->id]);
            return $stat;
        } catch (Exception $e) {
            $this->logError('创建商品统计失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        try {
            $this->logInfo('更新商品统计开始', ['id' => $id, 'data' => $data]);
            $stat = $this->repository->update($id, $data);
            $this->logInfo('更新商品统计成功', ['id' => $id]);
            return $stat;
        } catch (Exception $e) {
            $this->logError('更新商品统计失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
