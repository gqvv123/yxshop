<?php

namespace app\repository;

use app\model\ItemParamValue;

/**
 * 商品参数值仓储
 */
class ItemParamValueRepository extends BaseRepository
{
    protected $model = ItemParamValue::class;

    /**
     * 按商品获取参数值
     */
    public function getByItem(int $itemId, int $appId = 0)
    {
        $query = $this->query()->where('item_id', $itemId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['templateItem'])->orderBy('id', 'asc')->get();
    }

    /**
     * 按模板项获取参数值
     */
    public function getByTemplateItem(int $templateItemId, int $appId = 0)
    {
        $query = $this->query()->where('template_item_id', $templateItemId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['item'])->orderBy('id', 'desc')->get();
    }

    /**
     * 后台分页列表
     */
    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        $query = $this->query()->orderBy('id', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if (!empty($filters['item_id'])) {
            $query->where('item_id', $filters['item_id']);
        }
        if (!empty($filters['template_item_id'])) {
            $query->where('template_item_id', $filters['template_item_id']);
        }
        return $query->with(['item', 'templateItem'])->paginate($pageSize);
    }

    /**
     * 批量创建或更新商品参数值
     */
    public function upsertByItem(int $itemId, array $paramValues, int $appId = 0): bool
    {
        // 删除原有参数值
        $this->query()->where('item_id', $itemId)->delete();
        // 批量创建新参数值
        $now = time();
        $insertData = [];
        foreach ($paramValues as $templateItemId => $paramValue) {
            $insertData[] = [
                'item_id' => $itemId,
                'template_item_id' => $templateItemId,
                'param_value' => is_array($paramValue) ? json_encode($paramValue) : $paramValue,
                'app_id' => $appId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (!empty($insertData)) {
            return $this->createMany($insertData);
        }
        return true;
    }
}