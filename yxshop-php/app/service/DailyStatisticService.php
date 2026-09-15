<?php

namespace app\service;

use app\repository\DailyStatisticRepository;
use Exception;

/**
 * 每日统计服务类
 *
 * @property DailyStatisticRepository $repository
 */
class DailyStatisticService extends BaseService
{
    public function __construct(?DailyStatisticRepository $repository = null)
    {
        parent::__construct($repository ?? new DailyStatisticRepository());
    }

    public function getByDate(int $statDate, int $appId = 0)
    {
        try {
            return $this->repository->getByDate($statDate, $appId);
        } catch (Exception $e) {
            $this->logError('按日期获取统计失败', [
                'stat_date' => $statDate,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByDateRange(int $startDate, int $endDate, int $appId = 0)
    {
        try {
            return $this->repository->getByDateRange($startDate, $endDate, $appId);
        } catch (Exception $e) {
            $this->logError('获取日期范围统计失败', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getRecentDays(int $days = 7, int $appId = 0)
    {
        try {
            return $this->repository->getRecentDays($days, $appId);
        } catch (Exception $e) {
            $this->logError('获取最近N天统计失败', [
                'days' => $days,
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
            $this->logError('获取每日统计分页列表失败', [
                'app_id' => $appId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getSummary(int $startDate, int $endDate, int $appId = 0): array
    {
        try {
            return $this->repository->getSummary($startDate, $endDate, $appId);
        } catch (Exception $e) {
            $this->logError('获取汇总统计失败', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $this->logInfo('创建每日统计开始', ['data' => $data]);
            $stat = $this->repository->create($data);
            $this->logInfo('创建每日统计成功', ['id' => $stat->id]);
            return $stat;
        } catch (Exception $e) {
            $this->logError('创建每日统计失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        try {
            $this->logInfo('更新每日统计开始', ['id' => $id, 'data' => $data]);
            $stat = $this->repository->update($id, $data);
            $this->logInfo('更新每日统计成功', ['id' => $id]);
            return $stat;
        } catch (Exception $e) {
            $this->logError('更新每日统计失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
