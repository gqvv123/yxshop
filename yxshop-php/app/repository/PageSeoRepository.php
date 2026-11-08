<?php

namespace app\repository;

use app\model\PageSeo;

/**
 * 页面SEO仓储
 */
class PageSeoRepository extends BaseRepository
{
    protected $model = PageSeo::class;

    /**
     * 按页面Key获取SEO配置
     */
    public function getByPageKey(string $pageKey, int $appId = 0)
    {
        $query = $this->query()->where('page_key', $pageKey);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('id', 'desc')->get();
    }

    /**
     * 按页面Key和语言获取SEO配置
     */
    public function getByPageKeyAndLang(string $pageKey, string $langCode, int $appId = 0)
    {
        $query = $this->query()
            ->where('page_key', $pageKey)
            ->where('lang_code', $langCode);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->first();
    }

    /**
     * 按语言获取SEO配置
     */
    public function getByLang(string $langCode, int $appId = 0)
    {
        $query = $this->query()->where('lang_code', $langCode);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('page_key', 'asc')->get();
    }

    /**
     * 后台分页列表
     */
    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        $query = $this->query()->orderBy('page_key', 'asc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if (!empty($filters['lang_code'])) {
            $query->where('lang_code', $filters['lang_code']);
        }
        if (!empty($filters['page_key'])) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $filters['page_key']);
            $query->where('page_key', 'like', '%' . $escaped . '%');
        }
        if (!empty($filters['keyword'])) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $filters['keyword']);
            $query->where(function ($q) use ($escaped) {
                $q->where('title', 'like', '%' . $escaped . '%')
                  ->orWhere('keywords', 'like', '%' . $escaped . '%');
            });
        }
        return $query->paginate($pageSize);
    }

    /**
     * 批量创建或更新SEO配置
     */
    public function upsert(string $pageKey, string $langCode, array $data, int $appId = 0): PageSeo
    {
        $existing = $this->getByPageKeyAndLang($pageKey, $langCode, $appId);
        if ($existing) {
            $existing->fill($data);
            $existing->save();
            return $existing;
        }
        return $this->create(array_merge($data, [
            'page_key' => $pageKey,
            'lang_code' => $langCode,
            'app_id' => $appId,
        ]));
    }
}