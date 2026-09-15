<?php

namespace app\service;

use app\repository\AdvertisementRepository;
use app\exception\BusinessException;
use app\validate\AdValidate;
use Exception;

/**
 * 广告服务类
 *
 * @property AdvertisementRepository $repository
 */
class AdvertisementService extends BaseService
{
    public function __construct(?AdvertisementRepository $repository = null)
    {
        parent::__construct($repository ?? new AdvertisementRepository());
    }

    /**
     * 获取广告列表
     */
    public function getAdList($appId = 0, $status = null)
    {
        try {
            $this->logInfo('获取广告列表开始', ['app_id' => $appId, 'status' => $status]);

            $ads = $this->repository->listForAdmin((int) $appId, $status);

            $this->logInfo('获取广告列表成功', ['app_id' => $appId]);
            return $ads;

        } catch (Exception $e) {
            $this->logError('获取广告列表失败', [
                'app_id' => $appId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 创建广告
     */
    public function createAd(array $data)
    {
        try {
            $this->logInfo('创建广告开始', ['data' => $data]);

            $this->validateWith(AdValidate::class, 'create', $data);

            // 验证时间
            if ($data['start_time'] >= $data['end_time']) {
                throw new BusinessException('开始时间不能大于等于结束时间');
            }

            $ad = $this->repository->create($data);

            $this->logInfo('创建广告成功', ['ad_id' => $ad->id]);
            return $ad;

        } catch (Exception $e) {
            $this->logError('创建广告失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新广告
     */
    public function updateAd($id, array $data)
    {
        try {
            $this->logInfo('更新广告开始', ['id' => $id, 'data' => $data]);

            $this->validateWith(AdValidate::class, 'update', $data);

            $ad = $this->repository->findOrFail($id);

            // 验证时间
            if (isset($data['start_time']) && isset($data['end_time'])) {
                if ($data['start_time'] >= $data['end_time']) {
                    throw new BusinessException('开始时间不能大于等于结束时间');
                }
            }

            $ad = $this->repository->update($id, $data);

            $this->logInfo('更新广告成功', ['ad_id' => $id]);
            return $ad;

        } catch (Exception $e) {
            $this->logError('更新广告失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 删除广告
     */
    public function deleteAd($id)
    {
        try {
            $this->logInfo('删除广告开始', ['id' => $id]);

            $ad = $this->repository->findOrFail($id);

            // 软删除
            $ad->deleted_at = time();
            $ad->save();

            $this->logInfo('删除广告成功', ['ad_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除广告失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取有效广告
     */
    public function getActiveAds($position = null, $appId = 0)
    {
        try {
            return $this->repository->getActiveAds($position, $appId);

        } catch (Exception $e) {
            $this->logError('获取有效广告失败', [
                'position' => $position,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取即将开始的广告
     */
    public function getUpcomingAds($appId = 0, $days = 7)
    {
        try {
            return $this->repository->getUpcomingAds($appId, $days);

        } catch (Exception $e) {
            $this->logError('获取即将开始的广告失败', [
                'app_id' => $appId,
                'days' => $days,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取已过期的广告
     */
    public function getExpiredAds($appId = 0)
    {
        try {
            return $this->repository->getExpiredAds($appId);

        } catch (Exception $e) {
            $this->logError('获取已过期的广告失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取广告统计
     */
    public function getAdStats($appId = 0)
    {
        try {
            return $this->repository->getAdStats($appId);

        } catch (Exception $e) {
            $this->logError('获取广告统计失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        return $this->repository->paginateForList((int) $appId, (int) $pageSize, (string) $keyword);
    }
}
