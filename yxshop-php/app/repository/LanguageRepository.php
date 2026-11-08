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
            if (!$data) {
                return true;
            }
            // 一次性用 CASE WHEN 更新，避免逐条 update 造成 N+1。
            // 注意：Db::raw() 内的 ? 不会被参数绑定，因此这里必须用已 (int) 强转的字面量。
            $cases = [];
            $ids = [];
            foreach ($data as $id => $sort) {
                $id = (int) $id;
                if ($id <= 0) {
                    continue;
                }
                $cases[] = 'WHEN ' . $id . ' THEN ' . (int) $sort;
                $ids[] = $id;
            }
            if (!$ids) {
                return true;
            }

            $this->query()
                ->whereIn('id', $ids)
                ->update([
                    'sort' => \support\Db::raw(
                        'CASE id ' . implode(' ', $cases) . ' ELSE sort END'
                    ),
                    'updated_at' => time(),
                ]);
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

