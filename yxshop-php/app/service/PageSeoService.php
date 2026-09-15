<?php

namespace app\service;

use app\repository\PageSeoRepository;
use Exception;

/**
 * 页面SEO服务类
 *
 * @property PageSeoRepository $repository
 */
class PageSeoService extends BaseService
{
    public function __construct(?PageSeoRepository $repository = null)
    {
        parent::__construct($repository ?? new PageSeoRepository());
    }

    public function getByPageKey(string $pageKey, int $appId = 0)
    {
        try {
            return $this->repository->getByPageKey($pageKey, $appId);
        } catch (Exception $e) {
            $this->logError('按页面Key获取SEO配置失败', [
                'page_key' => $pageKey,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByPageKeyAndLang(string $pageKey, string $langCode, int $appId = 0)
    {
        try {
            return $this->repository->getByPageKeyAndLang($pageKey, $langCode, $appId);
        } catch (Exception $e) {
            $this->logError('按页面Key和语言获取SEO配置失败', [
                'page_key' => $pageKey,
                'lang_code' => $langCode,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByLang(string $langCode, int $appId = 0)
    {
        try {
            return $this->repository->getByLang($langCode, $appId);
        } catch (Exception $e) {
            $this->logError('按语言获取SEO配置失败', [
                'lang_code' => $langCode,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        try {
            return $this->repository->getPaginatedList($appId, $filters, $pageSize);
        } catch (Exception $e) {
            $this->logError('获取SEO分页列表失败', [
                'app_id' => $appId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function upsert(string $pageKey, string $langCode, array $data, int $appId = 0)
    {
        try {
            $this->logInfo('批量创建或更新SEO配置开始', [
                'page_key' => $pageKey,
                'lang_code' => $langCode
            ]);
            $seo = $this->repository->upsert($pageKey, $langCode, $data, $appId);
            $this->logInfo('批量创建或更新SEO配置成功', ['id' => $seo->id]);
            return $seo;
        } catch (Exception $e) {
            $this->logError('批量创建或更新SEO配置失败', [
                'page_key' => $pageKey,
                'lang_code' => $langCode,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $this->logInfo('创建SEO配置开始', ['data' => $data]);
            $seo = $this->repository->create($data);
            $this->logInfo('创建SEO配置成功', ['id' => $seo->id]);
            return $seo;
        } catch (Exception $e) {
            $this->logError('创建SEO配置失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        try {
            $this->logInfo('更新SEO配置开始', ['id' => $id, 'data' => $data]);
            $seo = $this->repository->update($id, $data);
            $this->logInfo('更新SEO配置成功', ['id' => $id]);
            return $seo;
        } catch (Exception $e) {
            $this->logError('更新SEO配置失败', [
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
            $this->logInfo('删除SEO配置开始', ['id' => $id]);
            $result = $this->repository->delete($id);
            $this->logInfo('删除SEO配置成功', ['id' => $id]);
            return $result;
        } catch (Exception $e) {
            $this->logError('删除SEO配置失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
