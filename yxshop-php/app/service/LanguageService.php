<?php

namespace app\service;

use app\model\Language;
use app\repository\LanguageRepository;
use app\service\cache\TieredCache;

/**
 * 语言服务类
 */
class LanguageService extends BaseService
{
    protected $repository;

    /**
     * 构造函数
     */
    public function __construct(LanguageRepository $repository = null)
    {
        $repository = $repository ?? new LanguageRepository();
        parent::__construct($repository);
    }

    /**
     * 获取默认语言
     */
    public function getDefault()
    {
        try {
            return TieredCache::remember('language_default', 3600, function () {
                return $this->repository->getDefault();
            });
        } catch (\Exception $e) {
            $this->logError('获取默认语言失败', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 根据代码获取语言
     */
    public function getByCode(string $code)
    {
        try {
            return TieredCache::remember("language_{$code}", 3600, function () use ($code) {
                return $this->repository->findByCode($code);
            });
        } catch (\Exception $e) {
            $this->logError('根据代码获取语言失败', ['code' => $code, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 获取所有启用的语言
     */
    public function getActive()
    {
        try {
            $result = TieredCache::remember('languages_active', 3600, function () {
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
            $this->logError('获取启用语言列表失败', ['error' => $e->getMessage()]);
            return collect([]);
        }
    }

    /**
     * 创建语言
     */
    public function create(array $data)
    {
        try {
            $this->logInfo('创建语言开始', ['data' => $data]);


            // 检查代码是否已存在
            if ($this->repository->codeExists($data['code'])) {
                throw new \Exception('语言代码已存在');
            }

            $language = $this->repository->create($data);
            
            // 清除缓存
            $this->clearCache();

            $this->logInfo('创建语言成功', ['language_id' => $language->id]);
            return $language;

        } catch (\Exception $e) {
            $this->logError('创建语言失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        try {
            $this->logInfo('更新语言开始', ['id' => $id, 'data' => $data]);


            if (isset($data['code']) && $this->repository->codeExists($data['code'], $id)) {
                throw new \Exception('语言代码已存在');
            }

            $language = $this->repository->update($id, $data);

            if (!$language) {
                throw new \Exception('语言不存在');
            }

            $this->clearCache();

            $this->logInfo('更新语言成功', ['language_id' => $id]);
            return $language;

        } catch (\Exception $e) {
            $this->logError('更新语言失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function delete($id)
    {
        try {
            $this->logInfo('删除语言开始', ['id' => $id]);

            $language = $this->repository->find($id);

            if (!$language) {
                throw new \Exception('语言不存在');
            }

            if ($language->is_default) {
                throw new \Exception('不能删除默认语言');
            }

            $result = $this->repository->delete($id);

            $this->clearCache();

            $this->logInfo('删除语言成功', ['id' => $id]);
            return $result;

        } catch (\Exception $e) {
            $this->logError('删除语言失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 设置默认语言
     */
    public function setDefault(int $id): bool
    {
        try {
            $this->logInfo('设置默认语言开始', ['id' => $id]);

            $result = $this->repository->setDefault($id);
            
            if (!$result) {
                throw new \Exception('设置默认语言失败');
            }

            // 清除缓存
            $this->clearCache();

            $this->logInfo('设置默认语言成功', ['id' => $id]);
            return true;

        } catch (\Exception $e) {
            $this->logError('设置默认语言失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 切换语言
     */
    public function switchLanguage(string $code): bool
    {
        try {
            $language = $this->getByCode($code);
            
            if (!$language) {
                throw new \Exception('语言不存在');
            }

            // 设置到 session
            session()->put('locale', $code);
            
            $this->logInfo('切换语言成功', ['code' => $code]);
            return true;

        } catch (\Exception $e) {
            $this->logError('切换语言失败', [
                'code' => $code,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 获取当前语言
     */
    public function getCurrentLanguage()
    {
        try {
            $code = session()->get('locale');
            
            if ($code) {
                $language = $this->getByCode($code);
                if ($language) {
                    return $language;
                }
            }
            
            return $this->getDefault();

        } catch (\Exception $e) {
            $this->logError('获取当前语言失败', ['error' => $e->getMessage()]);
            return $this->getDefault();
        }
    }

    /**
     * 清除缓存
     */
    protected function clearCache()
    {
        TieredCache::delete('language_default');
        TieredCache::delete('languages_active');
        
        // 清除所有语言代码缓存
        $languages = $this->repository->getActive();
        foreach ($languages as $language) {
            TieredCache::delete("language_{$language->code}");
        }
    }
}

