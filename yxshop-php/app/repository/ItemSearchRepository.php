<?php

namespace app\repository;

use app\model\ItemSearch;

/**
 * 商品搜索记录仓储类
 */
class ItemSearchRepository extends BaseRepository
{
    protected $model = ItemSearch::class;

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        $query = $this->query()->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($keyword) {
            $query->where('keyword', 'like', '%' . $keyword . '%');
        }

        return $query->paginate($pageSize);
    }

    /**
     * 获取用户搜索记录
     */
    public function getUserSearches($userId, $appId = 0, $limit = 20)
    {
        $query = $this->query()
            ->where('user_id', $userId)
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
     * 添加搜索记录（如已存在相同关键词则更新时间）
     */
    public function addSearch($userId, $keyword, $appId = 0)
    {
        // 检查是否已搜索过
        $existing = $this->query()
            ->where('user_id', $userId)
            ->where('keyword', $keyword)
            ->first();

        if ($existing) {
            // 更新搜索时间
            $existing->created_at = time();
            $existing->save();
            return $existing;
        }

        return $this->create([
            'user_id'      => $userId,
            'keyword'      => $keyword,
            'app_id'       => $appId,
            'ip'           => '',
            'result_count' => 0,
            'created_at'   => time(),
        ]);
    }

    /**
     * 获取热门搜索（按关键词分组统计出现次数）
     */
    public function getHotSearches($appId = 0, $limit = 10)
    {
        $query = $this->query()
            ->selectRaw('keyword, COUNT(*) as total_count')
            ->groupBy('keyword')
            ->orderBy('total_count', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * 获取搜索统计
     */
    public function getSearchStats($appId = 0, $days = 30)
    {
        $startTime = time() - ($days * 24 * 3600);
        $query = $this->query()->where('created_at', '>=', $startTime);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total_searches' => $query->count(),
            'unique_keywords' => $query->distinct('keyword')->count(),
            'total_users' => $query->distinct('user_id')->count(),
        ];
    }

    /**
     * 清理过期搜索记录
     */
    public function cleanExpiredSearches($days = 90)
    {
        $expiredTime = time() - ($days * 24 * 3600);
        
        return $this->query()
            ->where('created_at', '<', $expiredTime)
            ->delete();
    }
}
