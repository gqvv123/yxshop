<?php

namespace app\service;

use app\repository\ItemAttributeRepository;
use Exception;

class ItemAttributeService extends BaseService
{
    public function __construct(?ItemAttributeRepository $repository = null)
    {
        parent::__construct($repository ?? new ItemAttributeRepository());
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        return $this->repository->getList($appId, $pageSize, $keyword);
    }

    /**
     * 获取属性列表（供商品编辑选择，不分页）
     */
    public function getEnabledList($appId = 0)
    {
        return $this->repository->getEnabledList($appId);
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

    public function delete($id)
    {
        return $this->repository->softDelete($id);
    }
}
