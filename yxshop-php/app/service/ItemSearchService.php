<?php

namespace app\service;

use app\repository\ItemSearchRepository;
use Exception;

class ItemSearchService extends BaseService
{
    public function __construct(?ItemSearchRepository $repository = null)
    {
        parent::__construct($repository ?? new ItemSearchRepository());
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        return $this->repository->getList($appId, $pageSize, $keyword);
    }

    public function getDetail($id)
    {
        return $this->repository->find($id);
    }

    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    public function update($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    /**
     * 删除搜索记录（硬删除，该表无 deleted_at 列）
     */
    public function delete($id)
    {
        $model = $this->repository->findOrFail($id);
        return $model->delete();
    }

    /**
     * 清空搜索记录
     */
    public function clear($appId = 0)
    {
        $query = $this->repository->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->delete();
    }
}
