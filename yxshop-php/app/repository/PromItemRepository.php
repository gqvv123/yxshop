<?php

namespace app\repository;

use app\model\PromItem;

class PromItemRepository extends BaseRepository
{
    protected $model = PromItem::class;

    public function getPromItems($promId = null, $appId = 0)
    {
        $query = $this->query()
            ->with(['promotion', 'item'])
            ->orderBy('sort', 'asc');

        if ($promId !== null) {
            $query->where('prom_id', $promId);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    public function getAvailablePromItems($promId = null, $appId = 0)
    {
        $query = $this->query()
            ->where('prom_status', PromItem::STATUS_ENABLED)
            ->where('prom_stock', '>', 0)
            ->with(['promotion', 'item'])
            ->orderBy('sort', 'asc');

        if ($promId !== null) {
            $query->where('prom_id', $promId);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    public function getPromItemStats($promId = null, $appId = 0)
    {
        $query = $this->query();

        if ($promId !== null) {
            $query->where('prom_id', $promId);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total' => $query->count(),
            'enabled' => $query->where('prom_status', PromItem::STATUS_ENABLED)->count(),
            'disabled' => $query->where('prom_status', PromItem::STATUS_DISABLED)->count(),
            'sold_out' => $query->where('prom_status', PromItem::STATUS_SOLD_OUT)->count(),
            'total_stock' => $query->sum('prom_stock'),
            'total_sales' => $query->sum('prom_sales'),
        ];
    }

    public function getPromByItemId($itemId, $appId = 0)
    {
        $query = $this->query()
            ->where('item_id', $itemId)
            ->where('prom_status', PromItem::STATUS_ENABLED)
            ->where('prom_stock', '>', 0);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->first();
    }

    public function paginatedForAdmin(int $appId = 0, int $pageSize = 20, string $keyword = '')
    {
        $query = $this->model->newQuery()
            ->with(['promotion', 'item'])
            ->orderBy('id', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if ($keyword !== '') {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $keyword);
            $query->where(function ($q) use ($escaped) {
                $q->where('id', 'like', '%' . $escaped . '%')
                  ->orWhereHas('promotion', fn($pq) => $pq->where('name', 'like', '%' . $escaped . '%'))
                  ->orWhereHas('item', fn($iq) => $iq->where('name', 'like', '%' . $escaped . '%'));
            });
        }
        return $query->paginate($pageSize);
    }
}