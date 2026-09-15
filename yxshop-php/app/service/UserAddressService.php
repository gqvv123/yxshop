<?php

namespace app\service;

use app\repository\UserAddressRepository;
use app\exception\BusinessException;
use app\validate\UserAddressValidate;
use Exception;

/**
 * 用户地址服务
 *
 * 4 层架构：所有数据访问通过 UserAddressRepository；设置默认/创建默认用事务。
 *
 * @property UserAddressRepository $repository
 */
class UserAddressService extends BaseService
{
    public function __construct(?UserAddressRepository $repository = null)
    {
        parent::__construct($repository ?? new UserAddressRepository());
    }

    /**
     * 获取用户地址列表
     */
    public function getUserAddresses($userId, $appId = 0)
    {
        try {
            return $this->repository->getUserAddresses($userId, $appId);
        } catch (Exception $e) {
            $this->logError('获取用户地址列表失败',
                compact('userId', 'appId') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 创建用户地址（创建默认地址时事务保证唯一）
     */
    public function createUserAddress(array $data)
    {
        try {
            $this->logInfo('创建用户地址开始', ['data' => $data]);
            $this->validateWith(UserAddressValidate::class, 'create', $data);

            return $this->transaction(function () use ($data) {
                if (!empty($data['is_default'])) {
                    $this->repository->clearDefaultForUser((int) $data['user_id']);
                }
                $address = $this->repository->create($data);
                $this->logInfo('创建用户地址成功', ['address_id' => $address->id]);
                return $address;
            });
        } catch (Exception $e) {
            $this->logError('创建用户地址失败', ['data' => $data, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 更新用户地址（含归属校验 + 默认切换事务）
     */
    public function updateUserAddress($id, array $data, $userId)
    {
        try {
            $this->validateWith(UserAddressValidate::class, 'update', $data);

            $address = $this->repository->findUserAddress((int) $id, (int) $userId);
            if (!$address) {
                throw new BusinessException('地址不存在或无权访问', 404);
            }

            return $this->transaction(function () use ($id, $userId, $data) {
                if (!empty($data['is_default'])) {
                    $this->repository->clearDefaultForUser((int) $userId, (int) $id);
                }
                $address = $this->repository->update($id, $data);
                $this->logInfo('更新用户地址成功', ['address_id' => $id]);
                return $address;
            });
        } catch (Exception $e) {
            $this->logError('更新用户地址失败',
                compact('id', 'userId') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 删除用户地址（含归属校验）
     */
    public function deleteUserAddress($id, $userId)
    {
        try {
            $address = $this->repository->findUserAddress((int) $id, (int) $userId);
            if (!$address) {
                throw new BusinessException('地址不存在或无权访问', 404);
            }
            $this->repository->delete($id);
            $this->logInfo('删除用户地址成功', ['address_id' => $id]);
            return true;
        } catch (Exception $e) {
            $this->logError('删除用户地址失败',
                compact('id', 'userId') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 设置默认地址（含归属校验 + 事务）
     */
    public function setDefaultAddress($id, $userId)
    {
        try {
            $address = $this->repository->findUserAddress((int) $id, (int) $userId);
            if (!$address) {
                throw new BusinessException('地址不存在或无权访问', 404);
            }
            return $this->transaction(function () use ($id, $userId) {
                $this->repository->setDefaultAddress($id, $userId);
                $this->logInfo('设置默认地址成功', ['address_id' => $id]);
                return true;
            });
        } catch (Exception $e) {
            $this->logError('设置默认地址失败',
                compact('id', 'userId') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 获取默认地址
     */
    public function getDefaultAddress($userId, $appId = 0)
    {
        try {
            return $this->repository->getUserDefaultAddress($userId, $appId);
        } catch (Exception $e) {
            $this->logError('获取默认地址失败',
                compact('userId', 'appId') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 搜索用户地址
     */
    public function searchUserAddresses($userId, $keyword, $appId = 0)
    {
        try {
            return $this->repository->searchUserAddresses($userId, $keyword, $appId);
        } catch (Exception $e) {
            $this->logError('搜索用户地址失败',
                compact('userId', 'keyword', 'appId') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 用户地址统计
     */
    public function getUserAddressStats($userId, $appId = 0)
    {
        try {
            return $this->repository->getUserAddressStats($userId, $appId);
        } catch (Exception $e) {
            $this->logError('获取用户地址统计失败',
                compact('userId', 'appId') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 后台分页列表
     */
    public function getPaginatedList($appId, $pageSize = 20)
    {
        return $this->repository->getPaginatedByApp((int) $appId, (int) $pageSize);
    }

    /**
     * 兼容旧 Controller 调用
     */
    public function getListByUserId(int $userId)
    {
        return $this->repository->getUserAddresses($userId, 0);
    }

    public function getDefaultByUserId(int $userId)
    {
        return $this->repository->getUserDefaultAddress($userId, 0);
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        return $this->repository->getPaginatedByApp((int) $appId, (int) $pageSize);
    }
}
