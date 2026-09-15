<?php

namespace app\repository;

use app\model\ItemView;

/**
 * 商品浏览记录仓储类
 */
class ItemViewRepository extends BaseRepository
{
    protected $model = ItemView::class;

    /**
     * 获取用户浏览记录
     */
    public function getUserViews($userId, $appId = 0, $limit = 20)
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->with(['item.images'])
            ->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * 用户浏览记录分页查询（带关联）
     */
    public function getPaginatedByUserWithItem(int $userId, int $page, int $pageSize)
    {
        $base = $this->query()
            ->where('user_id', $userId)
            ->with(['item.images']);

        $total = (clone $base)->count();
        $offset = ($page - 1) * $pageSize;
        $items = $base->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($pageSize)
            ->get();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items, $total, $pageSize, $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }

    /**
     * 按用户删除所有浏览记录
     */
    public function deleteByUser(int $userId): int
    {
        return $this->query()->where('user_id', $userId)->delete();
    }

    /**
     * 按ID和用户查找浏览记录
     */
    public function findByIdAndUser(int $viewId, int $userId): ?ItemView
    {
        return $this->query()
            ->where('id', $viewId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * 添加浏览记录
     */
    public function addView($userId, $itemId, $appId = 0)
    {
        // 检查是否已浏览过
        $existing = $this->query()
            ->where('user_id', $userId)
            ->where('item_id', $itemId)
            ->first();

        if ($existing) {
            // 更新浏览时间
            $existing->created_at = time();
            $existing->save();
            return $existing;
        }

        return $this->create([
            'user_id' => $userId,
            'item_id' => $itemId,
            'app_id' => $appId,
        ]);
    }

    /**
     * 获取商品浏览统计
     */
    public function getItemViewStats($itemId, $days = 30)
    {
        $startTime = time() - ($days * 24 * 3600);
        
        return $this->query()
            ->where('item_id', $itemId)
            ->where('created_at', '>=', $startTime)
            ->count();
    }

    /**
     * 获取用户浏览统计
     */
    public function getUserViewStats($userId, $appId = 0)
    {
        $query = $this->query()->where('user_id', $userId);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total' => $query->count(),
            'today' => $query->where('created_at', '>=', strtotime('today'))->count(),
            'this_week' => $query->where('created_at', '>=', strtotime('this week'))->count(),
            'this_month' => $query->where('created_at', '>=', strtotime('this month'))->count(),
        ];
    }

    /**
     * 清理过期浏览记录
     */
    public function cleanExpiredViews($days = 90)
    {
        $expiredTime = time() - ($days * 24 * 3600);
        
        return $this->query()
            ->where('created_at', '<', $expiredTime)
            ->delete();
    }
}
