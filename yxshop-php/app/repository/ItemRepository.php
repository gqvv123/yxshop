<?php

namespace app\repository;

use app\model\Item;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 商品仓储
 *
 * 设计原则：所有数据访问通过意图揭示方法暴露，Service 不在外部拼 query()->where(...)
 * 字段对齐 yxshop_items 表实际结构（name/total_sales/stock/is_recommended/is_hot/is_new）
 */
class ItemRepository extends BaseRepository
{
    protected $model = Item::class;

    // ---------------- 单商品查询 ----------------

    /**
     * 含分类预加载
     */
    public function findWithCategory($id)
    {
        return $this->query()->with('category')->find($id);
    }

    /**
     * 全关联预加载（category/brand/images/skus/tags），不存在抛 ModelNotFound
     */
    public function findWithAllRelations($id)
    {
        return $this->query()
            ->with(['category', 'brand', 'images', 'skus', 'tags'])
            ->find($id);
    }

    /**
     * 已上架商品详情（用户端，需 is_on_sale=1）
     * @return Item|null
     */
    public function findOnSaleDetail($id)
    {
        return $this->query()
            ->with(['category', 'brand', 'images', 'skus', 'attrs'])
            ->where('id', $id)
            ->where('is_on_sale', 1)
            ->first();
    }

    // ---------------- 列表 / 搜索 ----------------

    /**
     * 用户端商品列表（带分类/品牌/价格/排序）
     * @return array{data,total,page,page_size}
     */
    public function getListForUser(array $params): array
    {
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);

        $query = $this->query()
            ->with(['category', 'brand', 'images'])
            ->where('is_on_sale', 1);

        if (!empty($params['category_id'])) {
            // 单分类或多分类（传入 array 或 csv 由 Service 决定）
            if (is_array($params['category_id'])) {
                $query->whereIn('category_id', $params['category_id']);
            } else {
                $query->where('category_id', $params['category_id']);
            }
        }
        if (!empty($params['brand_id'])) {
            $query->where('brand_id', $params['brand_id']);
        }
        if (!empty($params['keyword'])) {
            $escapedKeyword = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $params['keyword']);
            $query->where('name', 'like', "%{$escapedKeyword}%");
        }
        if (!empty($params['min_price'])) {
            $query->where('sale_price', '>=', $params['min_price']);
        }
        if (!empty($params['max_price'])) {
            $query->where('sale_price', '<=', $params['max_price']);
        }
        if (!empty($params['app_id'])) {
            $query->where('app_id', $params['app_id']);
        }

        // 排序白名单
        $sort = $params['sort'] ?? 'sort';
        $order = ($params['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        switch ($sort) {
            case 'price':       $query->orderBy('sale_price', $order); break;
            case 'sales':       $query->orderBy('total_sales', 'desc'); break;
            case 'click':       $query->orderBy('click', 'desc'); break;
            case 'comment':     $query->orderBy('comment_count', 'desc'); break;
            default:            $query->orderBy('sort', 'asc'); break;
        }

        $total = (clone $query)->count();
        $offset = ($page - 1) * $pageSize;
        $items = $query->offset($offset)->limit($pageSize)->get();

        return [
            'data' => $items,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 后台商品搜索（含分页）
     */
    public function searchAdminItems(int $appId, array $filters = [], int $pageSize = 20)
    {
        $query = $this->query()->where('app_id', $appId);

        if (!empty($filters['keyword'])) {
            $keyword = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $filters['keyword']);
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('id', 'like', "%{$keyword}%");
            });
        }
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_on_sale', $filters['status']);
        }

        return $query->with(['category', 'brand'])
            ->orderBy('id', 'desc')
            ->paginate($pageSize);
    }

    /**
     * 按关键字搜索（BaseRepository::search 等价实现，字段对齐 Item 表）
     */
    public function search(string $keyword, array $fields = [], int $page = 1, int $limit = 15): LengthAwarePaginator
    {
        $query = $this->query()
            ->where('is_on_sale', 1)
            ->with(['images']);

        if (empty($fields)) {
            $fields = ['name'];
        }

        if ($keyword !== '' && $fields) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $keyword);
            $query->where(function ($q) use ($fields, $escaped) {
                foreach ($fields as $field) {
                    $q->orWhere($field, 'like', '%' . $escaped . '%');
                }
            });
        }

        $total = (clone $query)->count();
        $offset = ($page - 1) * $limit;
        $items = $query->orderBy('total_sales', 'desc')
            ->offset($offset)->limit($limit)->get();

        return new LengthAwarePaginator(
            $items, $total, $limit, $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }

    /**
     * 按分类
     */
    public function getByCategory($catId, $limit = 20)
    {
        return $this->query()
            ->where('category_id', $catId)
            ->where('is_on_sale', 1)
            ->with(['images'])
            ->orderBy('sort', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * 按品牌
     */
    public function getByBrand($brandId, $limit = 20)
    {
        return $this->query()
            ->where('brand_id', $brandId)
            ->where('is_on_sale', 1)
            ->with(['images'])
            ->orderBy('sort', 'asc')
            ->limit($limit)
            ->get();
    }

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

    // ---------------- 统计 / 报表 ----------------

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
     * 商品总数
     */
    public function countByApp(int $appId): int
    {
        return $this->query()->where('app_id', $appId)->count();
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

    // ---------------- 写操作 ----------------

    /**
     * 直接更新库存（汇总 SKU 库存后回写主表）
     * @return int affected rows
     */
    public function updateStockById(int $itemId, int $stock): int
    {
        return $this->query()->where('id', $itemId)->update(['stock' => $stock, 'updated_at' => time()]);
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

public function getOnSaleTrend(int $appId, string $period = 'month'): float
{
$now = time();
if ($period === 'week') {
$currentStart = $now - 7 * 86400;
$prevStart = $now - 14 * 86400;
$prevEnd = $now - 7 * 86400;
} else {
$currentStart = strtotime('first day of this month');
$prevStart = strtotime('first day of last month');
$prevEnd = strtotime('first day of this month');
}
// 当前在售总数
$current = $this->query()->where('app_id', $appId)->where('is_on_sale', 1)->count();
// 上一周期在售总数
$prev = $this->query()->where('app_id', $appId)->where('is_on_sale', 1)->where('created_at', '<', $prevEnd)->count();
return $prev > 0 ? round(($current - $prev) / $prev * 100, 1) : ($current > 0 ? 100 : 0);
}

public function getValueTrend(int $appId, string $period = 'month'): float
{
$now = time();
if ($period === 'week') {
$prevEnd = $now - 7 * 86400;
} else {
$prevEnd = strtotime('first day of this month');
}
// 当前库存总值
$current = (float) ($this->query()->where('app_id', $appId)->where('is_on_sale', 1)->selectRaw('SUM(stock * sale_price) as total')->value('total') ?? 0);
// 上一周期库存总值
$prev = (float) ($this->query()->where('app_id', $appId)->where('is_on_sale', 1)->where('created_at', '<', $prevEnd)->selectRaw('SUM(stock * sale_price) as total')->value('total') ?? 0);
return $prev > 0 ? round(($current - $prev) / $prev * 100, 1) : ($current > 0 ? 100 : 0);
}

public function getSparklineData(int $appId, string $period = 'month', string $dimension = 'pay_count'): array
{
$allowedDimensions = ['pay_count', 'pay_amount', 'view_count', 'cart_count', 'favorite_count', 'order_count'];
if (!in_array($dimension, $allowedDimensions, true)) {
$dimension = 'pay_count';
}

$days = 7;
$result = array_fill(0, $days, 0);
$startDate = strtotime('-' . ($days - 1) . ' days');

// 优先查统计表
$rows = \app\model\ItemStatistic::where('app_id', $appId)
->where('stat_date', '>=', date('Y-m-d', $startDate))
->selectRaw("stat_date, {$dimension} as val")
->get();

foreach ($rows as $row) {
$dayIndex = (int) ((strtotime($row->stat_date) - $startDate) / 86400);
if ($dayIndex >= 0 && $dayIndex < $days) {
$result[$dayIndex] = (int) $row->val;
}
}

// 统计表无数据时，用商品创建数作为 fallback
if (array_sum($result) == 0) {
$items = $this->query()
->where('app_id', $appId)
->where('created_at', '>=', $startDate)
->selectRaw('created_at')
->get();
foreach ($items as $item) {
$itemTime = is_numeric($item->created_at) ? (int) $item->created_at : strtotime($item->created_at);
$dayIndex = (int) floor(($itemTime - $startDate) / 86400);
if ($dayIndex >= 0 && $dayIndex < $days) {
$result[$dayIndex]++;
}
}
}

return $result;
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

    public function getPriceHistoryByItem(int $itemId)
    {
        return \app\model\ItemSpecPriceHistory::where('item_id', $itemId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * 积分商品分页列表
     */
    public function getPointExchangeItems(int $appId = 0, int $page = 1, int $pageSize = 20, array $ids = []): LengthAwarePaginator
    {
        $query = $this->query()
            ->where('is_on_sale', 1)
            ->where('status', 1)
            ->where('points_exchange', '>', 0);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return $query->orderBy('sort', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 积分商品列表（不分页，带limit）
     */
    public function getPointExchangeList(int $appId = 0, int $limit = 10)
    {
        $query = $this->query()
            ->where('is_on_sale', 1)
            ->where('status', 1)
            ->where('points_exchange', '>', 0);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->orderBy('sort', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 查找积分商品（单个，需在售+积分兑换>0）
     */
    public function findPointExchangeItem($id)
    {
        return $this->query()
            ->where('id', $id)
            ->where('is_on_sale', 1)
            ->where('status', 1)
            ->where('points_exchange', '>', 0)
            ->first();
    }

    /**
     * 扣减商品库存
     */
    public function deductStock($itemId, int $quantity): bool
    {
        $item = $this->find($itemId);
        if (!$item) {
            return false;
        }
        $item->stock = max(0, $item->stock - $quantity);
        return $item->save();
    }

    public function exportQuery(int $appId, array $filters = [], array $ids = [])
    {
        $query = $this->query()->where('app_id', $appId);

        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }
        if (!empty($filters['keyword'])) {
            $escapedKeyword = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $filters['keyword']);
            $query->where('name', 'like', "%{$escapedKeyword}%");
        }
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_on_sale', $filters['status']);
        }

        return $query->with(['category', 'brand'])->orderBy('id', 'desc')->get();
    }

    /**
     * 原子增加库存
     */
    public function incrementStock(int $itemId, int $quantity): int
    {
        return $this->query()->where('id', $itemId)->increment('stock', $quantity);
    }

    /**
     * 原子减少库存（带库存守卫）
     */
    public function decrementStock(int $itemId, int $quantity): int
    {
        return $this->query()
            ->where('id', $itemId)
            ->where('stock', '>=', $quantity)
            ->decrement('stock', $quantity);
    }

    /**
     * 直接设置库存值（盘点）
     */
    public function setStock(int $itemId, int $stock): int
    {
        return $this->query()->where('id', $itemId)->update(['stock' => $stock, 'updated_at' => time()]);
    }
}
