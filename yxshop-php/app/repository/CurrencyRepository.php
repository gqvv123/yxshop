<?php

namespace app\repository;

use app\model\Currency;

/**
 * 货币仓储类
 */
class CurrencyRepository extends BaseRepository
{
    protected $model = Currency::class;

    /**
     * 获取默认货币
     */
    public function getDefault()
    {
        return $this->query()
            ->where('is_default', 1)
            ->where('status', 1)
            ->first() ?? $this->query()->where('status', 1)->first();
    }

    /**
     * 根据代码获取货币
     */
    public function findByCode(string $code)
    {
        return $this->query()
            ->where('code', $code)
            ->where('status', 1)
            ->first();
    }

    /**
     * 获取所有启用的货币
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
     * 设置默认货币
     */
    public function setDefault(int $id): bool
    {
        try {
            // 取消所有货币的默认状态
            $this->query()->where('is_default', 1)->update(['is_default' => 0]);
            
            // 设置指定货币为默认
            $currency = $this->find($id);
            if ($currency) {
                $currency->is_default = 1;
                $currency->status = 1;
                return $currency->save();
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 更新汇率
     */
    public function updateExchangeRate(int $id, float $rate): bool
    {
        try {
            $currency = $this->find($id);
            if ($currency) {
                $currency->exchange_rate = $rate;
                return $currency->save();
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 批量更新排序
     */
    /**
     * 分页（全局字典表，无 app_id 字段）
     */
    public function paginateAll(int $pageSize = 20)
    {
        return $this->query()
            ->orderBy('created_at', 'desc')
            ->paginate($pageSize);
    }
}

