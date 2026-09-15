<?php

namespace app\repository;

use app\model\ItemTranslation;

/**
 * 商品翻译仓储
 */
class ItemTranslationRepository extends BaseRepository
{
    protected $model = ItemTranslation::class;

    /**
     * 按商品获取翻译
     */
    public function getByItem(int $itemId, int $appId = 0)
    {
        $query = $this->query()->where('item_id', $itemId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('field_name', 'asc')->get();
    }

    /**
     * 按商品和语言获取翻译
     */
    public function getByItemAndLang(int $itemId, string $langCode, int $appId = 0)
    {
        $query = $this->query()
            ->where('item_id', $itemId)
            ->where('lang_code', $langCode);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('field_name', 'asc')->get();
    }

    /**
     * 按字段名获取翻译
     */
    public function getByFieldName(string $fieldName, int $appId = 0)
    {
        $query = $this->query()->where('field_name', $fieldName);
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
        if (!empty($filters['lang_code'])) {
            $query->where('lang_code', $filters['lang_code']);
        }
        if (!empty($filters['field_name'])) {
            $query->where('field_name', $filters['field_name']);
        }
        return $query->with(['item'])->paginate($pageSize);
    }

    /**
     * 批量创建或更新翻译
     */
    public function upsertByItem(int $itemId, string $langCode, array $translations, int $appId = 0): bool
    {
        // 删除原有翻译
        $this->query()
            ->where('item_id', $itemId)
            ->where('lang_code', $langCode)
            ->delete();
        // 批量创建新翻译
        $now = time();
        $insertData = [];
        foreach ($translations as $fieldName => $fieldValue) {
            $insertData[] = [
                'item_id' => $itemId,
                'lang_code' => $langCode,
                'field_name' => $fieldName,
                'field_value' => is_array($fieldValue) ? json_encode($fieldValue) : $fieldValue,
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