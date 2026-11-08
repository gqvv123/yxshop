<?php

namespace app\repository;

/**
 * ItemStatisticRepositoryTrait：从 ItemRepository.php 抽离的getHotItems、getRecommendedItems、getNewItems、getSoldOutItems、getItemStats、getLowStockItems、getProductReport、getProductCountByYear、getProductSellByMonth、getOnSaleCount、getTotalStockValue、getStockAlertCount、getCategoryContrib、getTrendingItems等方法。
 *
 * 拆分目的：控制单文件体积、聚焦单一职责；方法实现保持原样，行为完全等价。
 *
 * @package app\repository
 */
trait ItemStatisticRepositoryTrait
{
    /**
     * 热门商品
     * 没有热销标记时，自动回退到按销量排序的全部商品
     */
    public function getHotItems($limit = 10, $appId = 0)
    {
        $query = $this->query()
            ->where('is_on_sale', 1)
            ->where('is_hot', 1)
            ->with(['images'])
            ->orderBy('total_sales', 'desc')
            ->limit($limit);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        $result = $query->get();

        // 没有热销商品时，回退到按销量排序的普通商品
        if ($result->isEmpty()) {
            $fallbackQuery = $this->query()
                ->where('is_on_sale', 1)
                ->with(['images'])
                ->orderBy('total_sales', 'desc')
                ->orderBy('sort', 'asc')
                ->limit($limit);
            if ($appId > 0) {
                $fallbackQuery->where('app_id', $appId);
            }
            $result = $fallbackQuery->get();
        }
        return $result;
    }

    /**
     * 推荐商品
     * 没有推荐标记时，自动回退到按排序的全部商品
     */
    public function getRecommendedItems($limit = 10, $appId = 0)
    {
        $query = $this->query()
            ->where('is_on_sale', 1)
            ->where('is_recommended', 1)
            ->with(['images'])
            ->orderBy('sort', 'asc')
            ->limit($limit);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        $result = $query->get();

        // 没有推荐商品时，回退到普通商品
        if ($result->isEmpty()) {
            $fallbackQuery = $this->query()
                ->where('is_on_sale', 1)
                ->with(['images'])
                ->orderBy('sort', 'asc')
                ->orderBy('created_at', 'desc')
                ->limit($limit);
            if ($appId > 0) {
                $fallbackQuery->where('app_id', $appId);
            }
            $result = $fallbackQuery->get();
        }
        return $result;
    }

    /**
     * 新品
     * 没有新品标记时，自动回退到按创建时间排序的全部商品
     */
    public function getNewItems($limit = 10, $appId = 0)
    {
        $query = $this->query()
            ->where('is_on_sale', 1)
            ->where('is_new', 1)
            ->with(['images'])
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        $result = $query->get();

        // 没有新品标记时，回退到全部商品按创建时间排序
        if ($result->isEmpty()) {
            $fallbackQuery = $this->query()
                ->where('is_on_sale', 1)
                ->with(['images'])
                ->orderBy('created_at', 'desc')
                ->limit($limit);
            if ($appId > 0) {
                $fallbackQuery->where('app_id', $appId);
            }
            $result = $fallbackQuery->get();
        }
        return $result;
    }

    /**
     * 售罄商品
     */
    public function getSoldOutItems(int $appId, int $limit = 20)
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('stock', 0)
            ->with(['category'])
            ->limit($limit)
            ->get();
    }

    /**
     * 商品基础统计（每项 clone 防累加）
     */
    public function getItemStats($appId = 0): array
    {
        $base = $this->query();
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        return [
            'total'          => (clone $base)->count(),
            'on_sale'        => (clone $base)->where('is_on_sale', 1)->count(),
            'off_sale'       => (clone $base)->where('is_on_sale', 0)->count(),
            'low_stock'      => (clone $base)->where('stock', '<=', 10)->count(),
            'out_of_stock'   => (clone $base)->where('stock', '<=', 0)->count(),
        ];
    }

    /**
     * 库存预警商品
     */
    public function getLowStockItems($threshold = 10, $appId = 0)
    {
        $query = $this->query()
            ->where('stock', '<=', $threshold)
            ->where('is_on_sale', 1)
            ->with(['category', 'brand']);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('stock', 'asc')->get();
    }

    /**
     * 商品报表（低库存 + 总数）
     */
    public function getProductReport(int $appId): array
    {
        $base = $this->query()->where('app_id', $appId);
        return [
            'low_stock' => (clone $base)
                ->where('stock', '>', 0)
                ->where('stock', '<=', 10)
                ->count(),
            'total_product' => (clone $base)->count(),
        ];
    }

    /**
     * 按年统计商品创建数
     */
    public function getProductCountByYear(int $appId): array
    {
        return $this->query()
            ->where('app_id', $appId)
            ->selectRaw("YEAR(FROM_UNIXTIME(created_at)) as year, COUNT(*) as total")
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->keyBy('year')
            ->toArray();
    }

    /**
     * 按月统计销量
     */
    public function getProductSellByMonth(int $appId): array
    {
        return $this->query()
            ->where('app_id', $appId)
            ->selectRaw("DATE_FORMAT(FROM_UNIXTIME(created_at), '%Y-%m') as month, SUM(total_sales) as total_sales")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total_sales', 'month')
            ->toArray();
    }

    public function getOnSaleCount(int $appId): int
    {
        return $this->query()->where('app_id', $appId)->where('is_on_sale', 1)->count();
    }

    public function getTotalStockValue(int $appId): float
    {
        return (float) $this->query()
            ->where('app_id', $appId)
            ->where('is_on_sale', 1)
            ->selectRaw('SUM(stock * sale_price) as total')
            ->value('total') ?? 0;
    }

    public function getStockAlertCount(int $appId): int
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('is_on_sale', 1)
            ->whereRaw('stock <= stock_warning')
            ->where('stock_warning', '>', 0)
            ->count();
    }

    public function getCategoryContrib(int $appId): array
    {
        $rows = $this->query()
            ->where('app_id', $appId)
            ->where('is_on_sale', 1)
            ->selectRaw('category_id, SUM(total_sales) as total_sales')
            ->groupBy('category_id')
            ->orderByDesc('total_sales')
            ->get();

$totalSales = $rows->sum('total_sales');
// 如果没有销售数据，改用库存价值作为贡献度
if ($totalSales == 0) {
$rows = $this->query()
->where('app_id', $appId)
->where('is_on_sale', 1)
->where('stock', '>', 0)
->selectRaw('category_id, SUM(stock * sale_price) as total_value')
->groupBy('category_id')
->orderByDesc('total_value')
->get();
$totalSales = $rows->sum('total_value');
if ($totalSales == 0) return [];
// 用 total_value 替代 total_sales
foreach ($rows as $row) {
$row->total_sales = $row->total_value;
}
}

        // 批量查询分类名称，避免 N+1 问题
        $categoryIds = $rows->pluck('category_id')->unique()->filter()->values()->all();
        $categories = \app\model\Category::whereIn('id', $categoryIds)->pluck('name', 'id');

        $colors = ['var(--color-primary-container)', 'var(--color-tertiary-fixed-dim)', 'var(--color-secondary)', 'var(--color-error)', 'var(--color-warning)'];
        $result = [];
        $otherSales = 0;

        foreach ($rows as $i => $row) {
            $name = $categories->get($row->category_id) ?? '未分类';
            if ($i < 5) {
                $result[] = ['name' => $name, 'percent' => round($row->total_sales / $totalSales * 100, 1), 'color' => $colors[$i] ?? $colors[4]];
            } else {
                $otherSales += $row->total_sales;
            }
        }

        if ($otherSales > 0) {
            $result[] = ['name' => '其他', 'percent' => round($otherSales / $totalSales * 100, 1), 'color' => $colors[4]];
        }
        return $result;
    }

    public function getTrendingItems(int $appId, int $limit = 5): array
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('is_on_sale', 1)
            ->orderByDesc('total_sales')
            ->limit($limit)
            ->get()
            ->map(fn($item) => [
                'name' => $item->name,
                'growth' => null,
                'price' => (float) $item->sale_price,
                'image' => $item->images && $item->images->count() > 0 ? $item->images->first()->url : '',
            ])
            ->toArray();
    }
}
