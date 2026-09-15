<?php

namespace app\service;

use app\repository\DeliveryRuleRepository;
use app\exception\BusinessException;
use Exception;

/**
 * 配送模板服务类
 *
 * @property DeliveryRuleRepository $repository
 */
class DeliveryRuleService extends BaseService
{
    public function __construct(?DeliveryRuleRepository $repository = null)
    {
        parent::__construct($repository ?? new DeliveryRuleRepository());
    }

    /**
     * 获取配送模板列表
     */
    public function getDeliveryList($appId = 0)
    {
        try {
            $this->logInfo('获取配送模板列表开始', ['app_id' => $appId]);

            $deliveries = $this->repository->getDeliveries($appId);

            $this->logInfo('获取配送模板列表成功', ['app_id' => $appId]);
            return $deliveries;

        } catch (Exception $e) {
            $this->logError('获取配送模板列表失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 创建配送模板
     */
    public function createDelivery(array $data)
    {
        try {
            $this->logInfo('创建配送模板开始', ['data' => $data]);


            // 如果是默认模板，先取消其他默认模板
            if (!empty($data['is_default'])) {
                $this->repository->clearDefaultForApp((int) $data['app_id']);
            }

            $delivery = $this->repository->create($data);

            $this->logInfo('创建配送模板成功', ['delivery_id' => $delivery->delivery_id]);
            return $delivery;

        } catch (Exception $e) {
            $this->logError('创建配送模板失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新配送模板
     */
    public function updateDelivery($id, array $data)
    {
        try {
            $this->logInfo('更新配送模板开始', ['id' => $id, 'data' => $data]);


            $delivery = $this->repository->findOrFail($id);

            // 如果是默认模板，先取消其他默认模板
            if (!empty($data['is_default'])) {
                $this->repository->clearDefaultForApp((int) $delivery->app_id, $id);
            }

            $delivery = $this->repository->update($id, $data);

            $this->logInfo('更新配送模板成功', ['delivery_id' => $id]);
            return $delivery;

        } catch (Exception $e) {
            $this->logError('更新配送模板失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 删除配送模板
     */
    public function deleteDelivery($id)
    {
        try {
            $this->logInfo('删除配送模板开始', ['id' => $id]);

            $delivery = $this->repository->findOrFail($id);

            // 检查是否有订单使用
            if ($delivery->orders()->count() > 0) {
                throw new BusinessException('该配送模板有订单使用，无法删除');
            }

            $this->repository->delete($id);

            $this->logInfo('删除配送模板成功', ['delivery_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除配送模板失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取默认配送模板
     */
    public function getDefaultDelivery($appId = 0)
    {
        try {
            return $this->repository->getDefaultDelivery($appId);

        } catch (Exception $e) {
            $this->logError('获取默认配送模板失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 设置默认配送模板
     */
    public function setDefaultDelivery($id, $appId = 0)
    {
        try {
            $this->logInfo('设置默认配送模板开始', ['id' => $id, 'app_id' => $appId]);

            $this->repository->setDefaultDelivery($id, $appId);

            $this->logInfo('设置默认配送模板成功', ['delivery_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('设置默认配送模板失败', [
                'id' => $id,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取配送模板统计
     */
    public function getDeliveryStats($appId = 0)
    {
        try {
            return $this->repository->getDeliveryStats($appId);

        } catch (Exception $e) {
            $this->logError('获取配送模板统计失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getRuleList($page, $limit, array $filters = [])
    {
        return $this->repository->paginateRules((int) $page, (int) $limit, $filters);
    }

    public function getRuleById($id)
    {
        return $this->repository->findOrFail($id);
    }

    public function createRule(array $data)
    {
        return $this->repository->create($data);
    }

    public function updateRule($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function deleteRule($id)
    {
        return $this->repository->delete($id);
    }

    public function updateRuleStatus($id, $status)
    {
        $rule = $this->repository->findOrFail($id);
        $rule->status = $status;
        $rule->save();
        return $rule;
    }

    public function getRuleStats($appId = 0)
    {
        return $this->getDeliveryStats($appId);
    }
}
