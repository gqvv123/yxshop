<?php

namespace app\service;

use app\repository\ItemTypeRepository;
use app\model\ItemType;
use app\exception\BusinessException;
use Exception;

/**
 * 商品类型服务类
 * 
 * @property ItemTypeRepository $repository
 */
class ItemTypeService extends BaseService
{
    public function __construct(?ItemTypeRepository $repository = null)
    {
        parent::__construct($repository ?? new ItemTypeRepository());
    }

    /**
     * 获取类型列表
     */
    public function getTypeList($appId = 0)
    {
        try {
            $this->logInfo('获取商品类型列表开始', ['app_id' => $appId]);

            $types = $this->repository->getTypes($appId);

            $this->logInfo('获取商品类型列表成功', ['app_id' => $appId]);
            return $types;

        } catch (Exception $e) {
            $this->logError('获取商品类型列表失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 创建商品类型
     */
    public function createType(array $data)
    {
        try {
            $this->logInfo('创建商品类型开始', ['data' => $data]);

            $now = time();
            $data['created_at'] = $now;
            $data['updated_at'] = $now;

            $type = $this->repository->create($data);

            $this->logInfo('创建商品类型成功', ['type_id' => $type->type_id]);
            return $type;

        } catch (Exception $e) {
            $this->logError('创建商品类型失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新商品类型
     */
    public function updateType($id, array $data)
    {
        try {
            $this->logInfo('更新商品类型开始', ['id' => $id, 'data' => $data]);

            $data['updated_at'] = time();

            $type = $this->repository->findOrFail($id);
            $type = $this->repository->update($id, $data);

            $this->logInfo('更新商品类型成功', ['type_id' => $id]);
            return $type;

        } catch (Exception $e) {
            $this->logError('更新商品类型失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 删除商品类型
     */
    public function deleteType($id)
    {
        try {
            $this->logInfo('删除商品类型开始', ['id' => $id]);

            $type = $this->repository->findOrFail($id);

            // 检查是否有商品使用该类型
            if ($type->items()->count() > 0) {
                throw new BusinessException('该类型下有商品，无法删除');
            }

            $this->repository->delete($id);

            $this->logInfo('删除商品类型成功', ['type_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除商品类型失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        return $this->repository->paginatedList((int) $appId, (int) $pageSize, (string) $keyword);
    }

    public function getDetail($id)
    {
        return $this->repository->find($id);
    }

    public function updateStatus($id, int $status): bool
    {
        $this->repository->update($id, ['status' => $status]);
        return true;
    }
}
