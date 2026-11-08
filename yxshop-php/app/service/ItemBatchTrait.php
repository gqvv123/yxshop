<?php

namespace app\service;
use app\exception\BusinessException;
use support\Db;

/**
 * ItemBatchTrait：从 ItemService.php 抽离的getSummary、batchPrice、adjustStock、batchAdjustStock、duplicateItem、getPriceHistory、exportItems、getSelectList、getMainImage、getRelatedItems、recordItemView等方法。
 *
 * 拆分目的：控制单文件体积、聚焦单一职责；方法实现保持原样，行为完全等价。
 *
 * @package app\service
 */
trait ItemBatchTrait
{
    public function getSummary(int $appId, string $period = 'month'): array
    {
        return [
            'onSaleCount' => $this->repository->getOnSaleCount($appId),
            'totalValue' => $this->repository->getTotalStockValue($appId),
            'stockAlert' => $this->repository->getStockAlertCount($appId),
            'onSaleTrend' => $this->repository->getOnSaleTrend($appId, $period),
            'valueTrend' => $this->repository->getValueTrend($appId, $period),
            'sparkline' => $this->repository->getSparklineData($appId, $period),
            'categoryContrib' => $this->repository->getCategoryContrib($appId),
            'trending' => $this->repository->getTrendingItems($appId),
        ];
    }

    public function batchPrice(array $ids, string $type, float $value, int $operatorId, string $operatorName = ''): array
    {
        $successCount = 0;
        $failedItems = [];

        return Db::connection()->transaction(function () use ($ids, $type, $value, $operatorId, $operatorName, &$successCount, &$failedItems) {
            foreach ($ids as $id) {
                $item = $this->repository->findOrFail($id);
                $oldPrice = (float) $item->sale_price;

                if ($type === 'fixed') {
                    $newPrice = $oldPrice + $value;
                } else {
                    $newPrice = $oldPrice * (1 + $value / 100);
                }

                if ($newPrice < 0.01) {
                    $failedItems[] = ['id' => $id, 'name' => $item->name, 'reason' => '改价后金额低于0.01'];
                    continue;
                }

                $item->sale_price = round($newPrice, 2);
                $item->save();

                $this->priceHistoryRepository->createLog([
                    'item_id' => $id,
                    'spec_id' => 0,
                    'old_price' => $oldPrice,
                    'new_price' => round($newPrice, 2),
                    'change_reason' => '批量改价',
                    'operator_id' => $operatorId,
                    'operator_name' => $operatorName,
                    'app_id' => $item->app_id,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);

                $successCount++;
            }

            return [
                'success_count' => $successCount,
                'failed_count' => count($failedItems),
                'failed_items' => $failedItems,
            ];
        });
    }

    public function adjustStock(int $itemId, string $type, int $quantity, string $remark, int $operatorId, string $operatorName = ''): array
    {
        $item = $this->repository->findOrFail($itemId);
        $beforeStock = (int) $item->stock;

        if ($type === 'in') {
            $afterStock = $beforeStock + $quantity;
            $changeType = 10;
            // 原子操作：increment 不会并发覆盖
            $affected = $this->repository->incrementStock($itemId, $quantity);
        } elseif ($type === 'out') {
            if ($quantity > $beforeStock) {
                throw new BusinessException('出库数量不能大于当前库存');
            }
            $afterStock = $beforeStock - $quantity;
            $changeType = 20;
            // 原子操作：WHERE stock >= quantity 条件下 decrement，防止并发超卖
            $affected = $this->repository->decrementStock($itemId, $quantity);
        } else {
            if ($quantity < 0) {
                throw new BusinessException('盘点库存不能为负数');
            }
            $afterStock = $quantity;
            $changeType = 30;
            // 盘点直接设置为指定值
            $affected = $this->repository->setStock($itemId, $afterStock);
        }

        if ($affected <= 0) {
            throw new BusinessException('库存调整失败，可能已被其他操作修改');
        }

        $item->refresh();
        $afterStock = (int) $item->stock;

        $now = time();
        $this->stockLogRepository->create([
            'item_id' => $itemId,
            'spec_id' => 0,
            'change_type' => $changeType,
            'change_quantity' => $type === 'check' ? ($afterStock - $beforeStock) : ($type === 'in' ? $quantity : -$quantity),
            'before_stock' => $beforeStock,
            'after_stock' => $afterStock,
            'change_reason' => $remark ?: ($type === 'check' ? '盘点' : ($type === 'in' ? '入库' : '出库')),
            'operator_id' => $operatorId,
            'operator_name' => $operatorName,
            'app_id' => $item->app_id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return ['id' => $itemId, 'stock' => $afterStock, 'change_type' => $type, 'before_stock' => $beforeStock, 'after_stock' => $afterStock];
    }

    public function batchAdjustStock(array $ids, string $type, int $quantity, string $remark, int $operatorId, string $operatorName = ''): array
    {
        $successCount = 0;
        $failedItems = [];
        foreach ($ids as $id) {
            try {
                $this->adjustStock($id, $type, $quantity, $remark, $operatorId, $operatorName);
                $successCount++;
            } catch (\Exception $e) {
                $item = $this->repository->find($id);
                $failedItems[] = ['id' => $id, 'name' => $item ? $item->name : '', 'reason' => $e->getMessage()];
            }
        }
        return ['success_count' => $successCount, 'failed_count' => count($failedItems), 'failed_items' => $failedItems];
    }

    public function duplicateItem(int $itemId, int $operatorId): array
    {
        return Db::connection()->transaction(function () use ($itemId, $operatorId) {
            $original = $this->repository->findWithAllRelations($itemId);
            if (!$original) {
                throw new BusinessException('商品不存在');
            }

            $newItem = $original->replicate();
            $newItem->name = $original->name . '(副本)';
            $newItem->is_on_sale = 0;
            $newItem->stock = 0;
            $newItem->total_sales = 0;
            $newItem->created_at = time();
            $newItem->updated_at = time();
            $newItem->save();

            if ($original->images) {
                foreach ($original->images as $img) {
                    $this->imageRepository->create([
                        'item_id' => $newItem->id,
                        'image_id' => $img->image_id,
                        'url' => $img->url,
                        'sort' => $img->sort,
                        'is_main' => $img->is_main,
                        'app_id' => $newItem->app_id,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);
                }
            }

            if ($original->skus) {
                foreach ($original->skus as $sku) {
                    $this->specPriceRepository->create([
                        'item_id' => $newItem->id,
                        'spec_key' => $sku->spec_key,
                        'spec_key_name' => $sku->spec_key_name,
                        'spec_values' => $sku->spec_values,
                        'price' => $sku->price,
                        'cost_price' => $sku->cost_price,
                        'market_price' => $sku->market_price,
                        'store_count' => 0,
                        'weight' => $sku->weight,
                        'sku' => $sku->sku,
                        'image' => $sku->image,
                        'app_id' => $newItem->app_id,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);
                }
            }

            return ['id' => $newItem->id, 'name' => $newItem->name, 'is_on_sale' => 0];
        });
    }

    public function getPriceHistory(int $itemId): array
    {
        $item = $this->repository->findOrFail($itemId);
        return ['list' => $this->repository->getPriceHistoryByItem($itemId)->toArray()];
    }

    public function exportItems(int $appId, array $filters, string $format = 'csv', array $ids = [])
    {
        $items = $this->repository->exportQuery($appId, $filters, $ids);
        if ($items->isEmpty()) {
            throw new BusinessException('当前无数据可导出');
        }

        $headers = ['商品名称', 'SKU', '分类', '品牌', '库存', '价格', '状态', '销量'];
        $rows = $items->map(function ($item) {
            return [
                $item->name,
                $item->skus && $item->skus->first() ? $item->skus->first()->sku : '',
                $item->category ? $item->category->name : '-',
                $item->brand ? $item->brand->name : '-',
                $item->stock,
                $item->sale_price,
                $item->is_on_sale ? '在售' : '下架',
                $item->total_sales,
            ];
        })->toArray();

        $filename = 'items_export_' . date('Ymd_His') . '.' . $format;

        if ($format === 'csv') {
            $output = "\xEF\xBB\xBF";
            $output .= implode(',', $headers) . "\n";
            foreach ($rows as $row) {
                $output .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', (string) ($v ?? '')) . '"', $row)) . "\n";
            }
            return response($output, 200, [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        return $items;
    }

    /**
     * 获取商品选择列表（后台用，仅返回 id/name/sale_price）
     */
    public function getSelectList(int $appId, string $keyword = '', int $pageSize = 20)
    {
        $query = $this->repository->query()
            ->where('app_id', $appId)
            ->where('is_on_sale', 1)
            ->select(['id', 'name', 'sale_price']);

        if ($keyword) {
            $escaped = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $keyword);
            $query->where('name', 'like', "%{$escaped}%");
        }

        $maxLimit = 500;
        if ($pageSize >= $maxLimit || $pageSize === 999) {
            return $query->orderBy('id', 'desc')->limit($maxLimit)->get();
        }

        return $query->orderBy('id', 'desc')->paginate($pageSize);
    }

    /**
     * 获取商品主图URL
     */
    public function getMainImage($item): string
    {
        if (!$item) return '';
        return \app\model\BaseModel::resolveAssetUrl($item->getMainImageAttribute() ?: '');
    }

    /**
     * 获取相关推荐商品（C端）
     */
    public function getRelatedItems($itemId, int $limit = 10): array
    {
        $item = $this->findOrFail($itemId);

        $related = $this->repository->query()
            ->where('is_on_sale', 1)
            ->where('id', '!=', $itemId)
            ->where(function ($q) use ($item) {
                $q->where('category_id', $item->category_id)
                  ->orWhere('brand_id', $item->brand_id);
            })
            ->with(['images'])
            ->orderBy('total_sales', 'desc')
            ->limit($limit)
            ->get();

        return $related->map(function ($r) {
            return [
                'id'          => (string) $r->id,
                'name'        => $r->name,
                'subtitle'    => $r->subtitle ?? '',
                'sale_price'  => $r->sale_price,
                'price'       => $r->price,
                'image'       => $this->getMainImage($r),
                'total_sales' => $r->total_sales,
            ];
        })->toArray();
    }

    /**
     * 记录商品浏览（C端足迹）
     * 已有记录则 view_count + 1 并更新 created_at（最近浏览时间），否则新建
     */
    public function recordItemView(string $itemId, int $userId): void
    {
        $item = $this->findOrFail($itemId);

        $existing = $this->viewRepository->query()
            ->where('user_id', $userId)
            ->where('item_id', $itemId)
            ->first();

        if ($existing) {
            $this->viewRepository->incrementViewCount((int) $existing->id);
        } else {
            $this->viewRepository->create([
                'user_id'    => $userId,
                'item_id'    => $itemId,
                'view_count' => 1,
                'app_id'     => $item->app_id,
                'created_at' => time(),
            ]);
        }
    }
}
