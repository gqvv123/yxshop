<?php

namespace app\service;

use app\model\Currency;
use app\repository\CurrencyRepository;
use app\service\cache\TieredCache;

/**
 * 货币服务类
 */
class CurrencyService extends BaseService
{
    protected $repository;

    /**
     * 构造函数
     */
    public function __construct(CurrencyRepository $repository = null)
    {
        $repository = $repository ?? new CurrencyRepository();
        parent::__construct($repository);
    }

    /**
     * 获取默认货币
     */
    public function getDefault()
    {
        try {
            return TieredCache::remember('currency_default', 3600, function () {
                return $this->repository->getDefault();
            });
        } catch (\Exception $e) {
            $this->logError('获取默认货币失败', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 根据代码获取货币
     */
    public function getByCode(string $code)
    {
        try {
            return TieredCache::remember("currency_{$code}", 3600, function () use ($code) {
                return $this->repository->findByCode($code);
            });
        } catch (\Exception $e) {
            $this->logError('根据代码获取货币失败', ['code' => $code, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 获取所有启用的货币
     */
    public function getActive()
    {
        try {
            $result = TieredCache::remember('currencies_active', 3600, function () {
                return $this->repository->getActive();
            });
            if ($result instanceof \Illuminate\Support\Collection) {
                return $result->map(function ($item) {
                    return is_array($item) ? (object)$item : $item;
                });
            }
            if (is_array($result)) {
                return collect($result)->map(function ($item) {
                    return is_array($item) ? (object)$item : $item;
                });
            }
            if ($result) {
                return collect([is_array($result) ? (object)$result : $result]);
            }
            return collect([]);
        } catch (\Exception $e) {
            $this->logError('获取启用货币列表失败', ['error' => $e->getMessage()]);
            return collect([]);
        }
    }

    /**
     * 创建货币
     */
    public function create(array $data)
    {
        try {
            $this->logInfo('创建货币开始', ['data' => $data]);


            // 检查代码是否已存在
            if ($this->repository->findByCode($data['code'])) {
                throw new \Exception('货币代码已存在');
            }

            $currency = $this->repository->create($data);
            
            // 清除缓存
            $this->clearCache();

            $this->logInfo('创建货币成功', ['currency_id' => $currency->id]);
            return $currency;

        } catch (\Exception $e) {
            $this->logError('创建货币失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新货币
     */
    // public function update(int $id, array $data)
    // {
    //     try {
    //         $this->logInfo('更新货币开始', ['id' => $id, 'data' => $data]);

    //         // 验证数据
    //         $this->validate($data, [
    //             'name' => 'string|max:50',
    //             'code' => 'string|max:10',
    //             'symbol' => 'string|max:10',
    //             'exchange_rate' => 'numeric|min:0',
    //         ]);

    //         $currency = $this->repository->update($id, $data);
            
    //         if (!$currency) {
    //             throw new \Exception('货币不存在');
    //         }

    //         // 清除缓存
    //         $this->clearCache();

    //         $this->logInfo('更新货币成功', ['currency_id' => $id]);
    //         return $currency;

    //     } catch (\Exception $e) {
    //         $this->logError('更新货币失败', [
    //             'id' => $id,
    //             'data' => $data,
    //             'error' => $e->getMessage()
    //         ]);
    //         throw $e;
    //     }
    // }

    // /**
    //  * 删除货币
    //  */
    // public function delete(int $id): bool
    // {
    //     try {
    //         $this->logInfo('删除货币开始', ['id' => $id]);

    //         $currency = $this->repository->find($id);
            
    //         if (!$currency) {
    //             throw new \Exception('货币不存在');
    //         }

    //         if ($currency->is_default) {
    //             throw new \Exception('不能删除默认货币');
    //         }

    //         $result = $this->repository->delete($id);
            
    //         // 清除缓存
    //         $this->clearCache();

    //         $this->logInfo('删除货币成功', ['id' => $id]);
    //         return $result;

    //     } catch (\Exception $e) {
    //         $this->logError('删除货币失败', [
    //             'id' => $id,
    //             'error' => $e->getMessage()
    //         ]);
    //         throw $e;
    //     }
    // }

    /**
     * 设置默认货币
     */
    public function setDefault(int $id): bool
    {
        try {
            $this->logInfo('设置默认货币开始', ['id' => $id]);

            $result = $this->repository->setDefault($id);
            
            if (!$result) {
                throw new \Exception('设置默认货币失败');
            }

            // 清除缓存
            $this->clearCache();

            $this->logInfo('设置默认货币成功', ['id' => $id]);
            return true;

        } catch (\Exception $e) {
            $this->logError('设置默认货币失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 货币转换
     */
    public function convert(float $amount, string $fromCode, string $toCode): float
    {
        try {
            if ($fromCode === $toCode) {
                return $amount;
            }

            $fromCurrency = $this->getByCode($fromCode);
            $toCurrency = $this->getByCode($toCode);

            if (!$fromCurrency || !$toCurrency) {
                throw new \Exception('货币不存在');
            }

            // 先转换为基础货币，再转换为目标货币
            $baseAmount = $amount / $fromCurrency->exchange_rate;
            return round($baseAmount * $toCurrency->exchange_rate, 2);

        } catch (\Exception $e) {
            $this->logError('货币转换失败', [
                'amount' => $amount,
                'from' => $fromCode,
                'to' => $toCode,
                'error' => $e->getMessage()
            ]);
            return $amount;
        }
    }

    /**
     * 清除缓存
     */
    protected function clearCache()
    {
        TieredCache::delete('currency_default');
        TieredCache::delete('currencies_active');
        
        // 清除所有货币代码缓存
        $currencies = $this->repository->getActive();
        foreach ($currencies as $currency) {
            TieredCache::delete("currency_{$currency->code}");
        }
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        return $this->repository->paginateAll((int) $pageSize);
    }
}

