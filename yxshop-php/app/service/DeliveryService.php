<?php

namespace app\service;

use app\repository\DeliveryRepository;
use app\repository\DeliveryRuleRepository;
use app\repository\ItemRepository;
use app\exception\BusinessException;
use support\Db;

/**
 * 配送模板服务
 *
 * 4 层架构：所有数据访问通过对应 Repository 意图揭示方法
 *
 * @property DeliveryRepository $repository
 */
class DeliveryService extends BaseService
{
    protected DeliveryRuleRepository $ruleRepository;
    protected ItemRepository $itemRepository;

    public function __construct(?DeliveryRepository $repository = null)
    {
        parent::__construct($repository ?? new DeliveryRepository());
        $this->ruleRepository = new DeliveryRuleRepository();
        $this->itemRepository = new ItemRepository();
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        return $this->repository->paginateForList((int) $appId, (int) $pageSize, (string) $keyword);
    }

    public function getDetail($id)
    {
        $delivery = $this->repository->findOrFail($id);
        $delivery->load('rules');
        return $delivery;
    }

    public function create(array $data)
    {
        return $this->transaction(function () use ($data) {
            $rules = $data['rules'] ?? [];
            unset($data['rules']);

            $delivery = $this->repository->create($data);

            foreach ($rules as $ruleData) {
                $ruleData['delivery_id'] = $delivery->id;
                $ruleData['app_id'] = $data['app_id'] ?? 0;
                if (empty($ruleData['created_at'])) $ruleData['created_at'] = time();
                $this->ruleRepository->create($ruleData);
            }

            $delivery->load('rules');
            return $delivery;
        });
    }

    public function update($id, array $data)
    {
        return $this->transaction(function () use ($id, $data) {
            $rules = $data['rules'] ?? [];
            unset($data['rules']);

            $delivery = $this->repository->findOrFail($id);
            $delivery->update($data);

            $existingRules = $this->ruleRepository->query()
                ->where('delivery_id', $id)->get()->keyBy('id');
            $submittedIds = [];

            foreach ($rules as $ruleData) {
                $ruleData['delivery_id'] = $id;
                $ruleData['app_id'] = $delivery->app_id;

                if (!empty($ruleData['id']) && $existingRules->has($ruleData['id'])) {
                    $existingRules[$ruleData['id']]->update($ruleData);
                    $submittedIds[] = $ruleData['id'];
                } else {
                    unset($ruleData['id']);
                    if (empty($ruleData['created_at'])) $ruleData['created_at'] = time();
                    $newRule = $this->ruleRepository->create($ruleData);
                    $submittedIds[] = $newRule->id;
                }
            }

            foreach ($existingRules as $existingRule) {
                if (!in_array($existingRule->id, $submittedIds)) {
                    $existingRule->delete();
                }
            }

            $delivery->load('rules');
            return $delivery;
        });
    }

    public function delete($id)
    {
        $delivery = $this->repository->findOrFail($id);

        $itemCount = $this->itemRepository->query()
            ->where('shipping_template_id', $id)->count();
        if ($itemCount > 0) {
            throw new BusinessException("该模板已被{$itemCount}个商品使用，无法删除");
        }

        $this->ruleRepository->query()
            ->where('delivery_id', $id)->delete();
        $delivery->delete();
        return true;
    }
}
