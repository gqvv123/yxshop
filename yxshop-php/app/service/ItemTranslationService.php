<?php

namespace app\service;

use app\repository\ItemTranslationRepository;
use Exception;

/**
 * 商品翻译服务类
 *
 * @property ItemTranslationRepository $repository
 */
class ItemTranslationService extends BaseService
{
    public function __construct(?ItemTranslationRepository $repository = null)
    {
        parent::__construct($repository ?? new ItemTranslationRepository());
    }

    public function getByItem(int $itemId, int $appId = 0)
    {
        try {
            return $this->repository->getByItem($itemId, $appId);
        } catch (Exception $e) {
            $this->logError('按商品获取翻译失败', [
                'item_id' => $itemId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByItemAndLang(int $itemId, string $langCode, int $appId = 0)
    {
        try {
            return $this->repository->getByItemAndLang($itemId, $langCode, $appId);
        } catch (Exception $e) {
            $this->logError('按商品和语言获取翻译失败', [
                'item_id' => $itemId,
                'lang_code' => $langCode,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByFieldName(string $fieldName, int $appId = 0)
    {
        try {
            return $this->repository->getByFieldName($fieldName, $appId);
        } catch (Exception $e) {
            $this->logError('按字段名获取翻译失败', [
                'field_name' => $fieldName,
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
            $this->logError('获取翻译分页列表失败', [
                'app_id' => $appId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function upsertByItem(int $itemId, string $langCode, array $translations, int $appId = 0)
    {
        try {
            $this->logInfo('批量更新翻译开始', [
                'item_id' => $itemId,
                'lang_code' => $langCode,
                'count' => count($translations)
            ]);
            $result = $this->repository->upsertByItem($itemId, $langCode, $translations, $appId);
            $this->logInfo('批量更新翻译成功', ['item_id' => $itemId]);
            return $result;
        } catch (Exception $e) {
            $this->logError('批量更新翻译失败', [
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $this->logInfo('创建翻译开始', ['data' => $data]);
            $translation = $this->repository->create($data);
            $this->logInfo('创建翻译成功', ['id' => $translation->id]);
            return $translation;
        } catch (Exception $e) {
            $this->logError('创建翻译失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        try {
            $this->logInfo('更新翻译开始', ['id' => $id, 'data' => $data]);
            $translation = $this->repository->update($id, $data);
            $this->logInfo('更新翻译成功', ['id' => $id]);
            return $translation;
        } catch (Exception $e) {
            $this->logError('更新翻译失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
