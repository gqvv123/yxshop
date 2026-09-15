<?php

namespace app\service;

use app\repository\MonthlyStatisticRepository;
use Exception;

/**
 * 每月统计服务类
 *
 * @property MonthlyStatisticRepository $repository
 */
class MonthlyStatisticService extends BaseService
{
    public function __construct(?MonthlyStatisticRepository $repository = null)
    {
        parent::__construct($repository ?? new MonthlyStatisticRepository());
    }

    public function getByMonth(int $statMonth, int $appId = 0)
    {
        try {
            return $this->repository->getByMonth($statMonth, $appId);
        } catch (Exception $e) {
            $this->logError('按月份获取统计失败', [
                'stat_month' => $statMonth,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByMonthRange(int $startMonth, int $endMonth, int $appId = 0)
    {
        try {
            return $this->repository->getByMonthRange($startMonth, $endMonth, $appId);
        } catch (Exception $e) {
            $this->logError('获取月份范围统计失败', [
                'start_month' => $startMonth,
                'end_month' => $endMonth,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getRecentMonths(int $months = 12, int $appId = 0)
    {
        try {
            return $this->repository->getRecentMonths($months, $appId);
        } catch (Exception $e) {
            $this->logError('获取最近N月统计失败', [
                'months' => $months,
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
            $this->logError('获取每月统计分页列表失败', [
                'app_id' => $appId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getSummary(int $startMonth, int $endMonth, int $appId = 0): array
    {
        try {
            return $this->repository->getSummary($startMonth, $endMonth, $appId);
        } catch (Exception $e) {
            $this->logError('获取月度汇总统计失败', [
                'start_month' => $startMonth,
                'end_month' => $endMonth,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $this->logInfo('创建月度统计开始', ['data' => $data]);
            $stat = $this->repository->create($data);
            $this->logInfo('创建月度统计成功', ['id' => $stat->id]);
            return $stat;
        } catch (Exception $e) {
            $this->logError('创建月度统计失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        try {
            $this->logInfo('更新月度统计开始', ['id' => $id, 'data' => $data]);
            $stat = $this->repository->update($id, $data);
            $this->logInfo('更新月度统计成功', ['id' => $id]);
            return $stat;
        } catch (Exception $e) {
            $this->logError('更新月度统计失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
