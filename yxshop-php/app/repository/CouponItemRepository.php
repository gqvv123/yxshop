<?php

namespace app\repository;

use app\model\CouponItem;

class CouponItemRepository extends BaseRepository
{
    protected $model = CouponItem::class;

    public function getByCouponId($couponId)
    {
        return $this->query()
            ->where('coupon_id', $couponId)
            ->with('item')
            ->get();
    }

    public function getByItemId($itemId)
    {
        return $this->query()
            ->where('item_id', $itemId)
            ->with('coupon')
            ->get();
    }

    public function addItemToCoupon($couponId, array $itemIds, $appId = 0)
    {
        $data = [];
        foreach ($itemIds as $itemId) {
            $data[] = [
                'coupon_id' => $couponId,
                'item_id' => $itemId,
                'app_id' => $appId,
                'created_at' => time(),
                'updated_at' => time(),
            ];
        }
        return $this->query()->insert($data);
    }

    public function removeItemFromCoupon($couponId, array $itemIds)
    {
        return $this->query()
            ->where('coupon_id', $couponId)
            ->whereIn('item_id', $itemIds)
            ->delete();
    }

    public function syncItemsForCoupon($couponId, array $itemIds, $appId = 0)
    {
        $this->model->newQuery()->where('coupon_id', $couponId)->delete();

        if (empty($itemIds)) {
            return true;
        }

        return $this->addItemToCoupon($couponId, $itemIds, $appId);
    }

    public function listCoupons(int $appId = 0, $status = null)
    {
        $query = $this->model->newQuery()->orderBy('created_at', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if ($status !== null) {
            $query->where('is_show', $status);
        }
        return $query->get();
    }
}