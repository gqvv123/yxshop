<?php

namespace app\service;
use Exception;

/**
 * ItemReportTrait：从 ItemService.php 抽离的getItemStats、getCount、getSoldOutItems、getProductReport、getProductReportList、getLatestItems、getProductByYear、getProductSellByMonth等方法。
 *
 * 拆分目的：控制单文件体积、聚焦单一职责；方法实现保持原样，行为完全等价。
 *
 * @package app\service
 */
trait ItemReportTrait
{
    public function getItemStats($appId = 0): array
    {
        try {
            return $this->repository->getItemStats($appId);
        } catch (Exception $e) {
            $this->logError('获取商品统计失败', ['app_id' => $appId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getCount($appId): int
    {
        return $this->repository->countByApp((int) $appId);
    }

    public function getSoldOutItems($appId, $limit = 20)
    {
        return $this->repository->getSoldOutItems((int) $appId, (int) $limit);
    }

    public function getProductReport($appId): array
    {
        return $this->repository->getProductReport((int) $appId);
    }

    /**
     * 商品报表列表（按销量排序）
     */
    public function getProductReportList($appId): array
    {
        // 说明：yxshop_items 没有 total_sales_amount / view_count 字段，
        // 销售额必须从已支付订单明细聚合，否则报表接口直接 SQL 报错。
        $query = $this->repository->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        $items = $query->orderBy('total_sales', 'desc')
            ->limit(100)
            ->get(['id', 'name', 'total_sales'])
            ->toArray();

        if (!$items) {
            return [];
        }

        $ids = array_column($items, 'id');
        $aggregated = $this->repository->aggregatePaidSales($ids);

        $result = [];
        foreach ($items as $item) {
            $row = $aggregated[$item['id']] ?? null;
            $salesCount = $row ? (int) $row->sales_count : (int) ($item['total_sales'] ?? 0);
            $result[] = [
                'id'           => $item['id'],
                'name'         => $item['name'],
                'sales_count'  => $salesCount,
                'sales_amount' => $row ? round((float) $row->sales_amount, 2) : 0.0,
                'view_count'   => 0,
            ];
        }

        return $result;
    }

    public function getLatestItems($appId, $limit = 10)
    {
        $query = $this->repository->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('id', 'desc')->limit($limit)->get();
    }

    public function getProductByYear($appId): array
    {
        return $this->repository->getProductCountByYear((int) $appId);
    }

    public function getProductSellByMonth($appId): array
    {
        return $this->repository->getProductSellByMonth((int) $appId);
    }
}
