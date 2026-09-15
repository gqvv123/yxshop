<?php

namespace app\service;

use app\repository\UserCouponRepository;
use Exception;

class UserCouponService extends BaseService
{
    public function __construct(?UserCouponRepository $repository = null)
    {
        parent::__construct($repository ?? new UserCouponRepository());
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
