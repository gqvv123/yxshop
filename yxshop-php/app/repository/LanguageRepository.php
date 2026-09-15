<?php

namespace app\repository;

use app\model\Language;

/**
 * 语言仓储类
 */
class LanguageRepository extends BaseRepository
{
    protected $model = Language::class;

    /**
     * 获取默认语言
     */
    public function getDefault()
    {
        return $this->query()
            ->where('is_default', 1)
            ->where('status', 1)
            ->first() ?? $this->query()->where('status', 1)->first();
    }

    /**
     * 根据代码获取语言
     */
    public function findByCode(string $code)
    {
        return $this->query()
            ->where('code', $code)
            ->where('status', 1)
            ->first();
    }

    /**
     * 获取所有启用的语言
     */
    public function getActive()
    {
        return $this->query()
            ->where('status', 1)
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * 设置默认语言
     */
    public function setDefault(int $id): bool
    {
        try {
            // 取消所有语言的默认状态
            $this->query()->where('is_default', 1)->update(['is_default' => 0]);
            
            // 设置指定语言为默认
            $language = $this->find($id);
            if ($language) {
                $language->is_default = 1;
                $language->status = 1;
                return $language->save();
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 批量更新排序
     */
    public function updateSort(array $data): bool
    {
        try {
            foreach ($data as $id => $sort) {
                $this->query()->where('id', $id)->update(['sort' => $sort]);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 检查语言代码是否存在
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = $this->query()
            ->where('code', $code)
            ->where('status', 1);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
}

