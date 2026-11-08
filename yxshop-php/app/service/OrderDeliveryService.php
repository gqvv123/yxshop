<?php

namespace app\service;

use app\repository\OrderDeliveryRepository;

/**
 * 业务服务：Order Delivery Service。
 *
 * @package app\service
 */
class OrderDeliveryService extends BaseService
{
    public function __construct(?OrderDeliveryRepository $repository = null)
    {
        parent::__construct($repository ?? new OrderDeliveryRepository());
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

    public function delete($id)
    {
        return $this->repository->softDelete($id);
    }
}
