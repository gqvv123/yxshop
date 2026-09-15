<?php

namespace app\service;

use app\repository\UserOauthRepository;
use Exception;

/**
 * 用户第三方授权服务类
 *
 * @property UserOauthRepository $repository
 */
class UserOauthService extends BaseService
{
    public function __construct(?UserOauthRepository $repository = null)
    {
        parent::__construct($repository ?? new UserOauthRepository());
    }

    public function getByUser(int $userId, int $appId = 0)
    {
        try {
            return $this->repository->getByUser($userId, $appId);
        } catch (Exception $e) {
            $this->logError('按用户获取授权记录失败', [
                'user_id' => $userId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function findByTypeAndOpenid(string $oauthType, string $openid, int $appId = 0)
    {
        try {
            return $this->repository->findByTypeAndOpenid($oauthType, $openid, $appId);
        } catch (Exception $e) {
            $this->logError('按类型和OpenID查找失败', [
                'oauth_type' => $oauthType,
                'openid' => $openid,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function findByUnionid(string $unionid, int $appId = 0)
    {
        try {
            return $this->repository->findByUnionid($unionid, $appId);
        } catch (Exception $e) {
            $this->logError('按UnionID查找失败', [
                'unionid' => $unionid,
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
            $this->logError('获取授权记录分页列表失败', [
                'app_id' => $appId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updateLastUsedAt(int $id)
    {
        try {
            $this->logInfo('更新最后使用时间开始', ['id' => $id]);
            $oauth = $this->repository->updateLastUsedAt($id);
            $this->logInfo('更新最后使用时间成功', ['id' => $id]);
            return $oauth;
        } catch (Exception $e) {
            $this->logError('更新最后使用时间失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $this->logInfo('创建授权记录开始', ['data' => $data]);
            $oauth = $this->repository->create($data);
            $this->logInfo('创建授权记录成功', ['id' => $oauth->id]);
            return $oauth;
        } catch (Exception $e) {
            $this->logError('创建授权记录失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        try {
            $this->logInfo('更新授权记录开始', ['id' => $id, 'data' => $data]);
            $oauth = $this->repository->update($id, $data);
            $this->logInfo('更新授权记录成功', ['id' => $id]);
            return $oauth;
        } catch (Exception $e) {
            $this->logError('更新授权记录失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
