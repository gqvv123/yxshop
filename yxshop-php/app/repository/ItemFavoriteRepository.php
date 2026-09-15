<?php

namespace app\repository;

use app\model\ItemFavorite;

/**
 * 商品收藏仓储类
 */
class ItemFavoriteRepository extends BaseRepository
{
    protected $model = ItemFavorite::class;

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        $query = $this->query()->with(['item'])->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('item_id', 'like', "%{$keyword}%")
                  ->orWhere('user_id', 'like', "%{$keyword}%")
                  ->orWhereHas('item', function ($iq) use ($keyword) {
                      $iq->where('name', 'like', "%{$keyword}%");
                  });
            });
        }

        $paginator = $query->paginate($pageSize);

        $list = [];
        foreach ($paginator->items() as $fav) {
            $list[] = [
                'id' => $fav->id,
                'user_id' => $fav->user_id,
                'item_id' => $fav->item_id,
                'item_name' => $fav->item->name ?? '',
                'price' => $fav->item->price ?? null,
                'created_at' => $fav->created_at,
            ];
        }

        return [
            'list' => $list,
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'page_size' => $pageSize,
        ];
    }

    /**
     * 获取用户收藏
     */
    public function getUserFavorites($userId, $appId = 0)
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->with(['item.images'])
            ->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 检查是否已收藏
     */
    public function isFavorited($userId, $itemId)
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('item_id', $itemId)
            ->exists();
    }

    /**
     * 获取用户收藏（分页含元数据）
     * @return array{data,total,page,page_size}
     */
    public function getUserFavoritesPaginated(int $userId, int $page = 1, int $pageSize = 20): array
    {
        $base = $this->query()
            ->where('user_id', $userId)
            ->with(['item.images']);

        $total = (clone $base)->count();
        $items = $base->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        return [
            'data' => $items,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 添加收藏（带时间戳）
     */
    public function addFavorite($userId, $itemId, $appId = 0)
    {
        $existing = $this->query()
            ->where('user_id', $userId)
            ->where('item_id', $itemId)
            ->first();

        if (!$existing) {
            $now = time();
            return $this->create([
                'user_id'    => $userId,
                'item_id'    => $itemId,
                'app_id'     => $appId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $existing;
    }

    /**
     * 取消收藏
     */
    public function removeFavorite($userId, $itemId)
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('item_id', $itemId)
            ->delete();
    }

    /**
     * 收藏统计（每项 clone 防累加 bug）
     */
    public function getFavoriteStats($userId, $appId = 0)
    {
        $base = $this->query()->where('user_id', $userId);
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        return [
            'total'      => (clone $base)->count(),
            'today'      => (clone $base)->where('created_at', '>=', strtotime('today'))->count(),
            'this_week'  => (clone $base)->where('created_at', '>=', strtotime('this week'))->count(),
            'this_month' => (clone $base)->where('created_at', '>=', strtotime('this month'))->count(),
        ];
    }

    /**
     * 获取商品收藏统计
     */
    public function getItemFavoriteStats($itemId)
    {
        return $this->query()
            ->where('item_id', $itemId)
            ->count();
    }
}
