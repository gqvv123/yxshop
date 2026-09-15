<?php

namespace app\service;

use app\repository\SpecRepository;
use app\repository\ItemSpecItemRepository;
use app\model\ItemSpec;
use app\model\ItemSpecItem;
use Exception;

class SpecService extends BaseService
{
    protected ItemSpecItemRepository $itemRepository;

    public function __construct(?SpecRepository $repository = null)
    {
        parent::__construct($repository ?? new SpecRepository());
        $this->itemRepository = new ItemSpecItemRepository();
    }

    /**
     * 规格分页列表（含规格值）
     */
    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        $paginator = $this->repository->getList($appId, $pageSize, $keyword);

        $list = $paginator->getCollection()->map(function ($spec) {
            return $this->formatSpec($spec);
        });

        return [
            'list' => $list->toArray(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    /**
     * 获取启用的规格列表（供商品编辑选择）
     */
    public function getEnabledList($appId = 0)
    {
        $specs = $this->repository->getEnabledList($appId);
        return $specs->map(function ($spec) {
            return $this->formatSpec($spec);
        })->toArray();
    }

    /**
     * 规格详情（含规格值）
     */
    public function getDetail($id)
    {
        $spec = $this->repository->findWithItems($id);
        if (!$spec) {
            return null;
        }
        return $this->formatSpec($spec);
    }

    /**
     * 创建规格（支持附带规格值）
     */
    public function create(array $data)
    {
        return $this->transaction(function () use ($data) {
            $now = time();
            $spec = $this->repository->create([
                'name'         => $data['name'] ?? '',
                'sort_order'   => $data['sort_order'] ?? 0,
                'status'       => $data['status'] ?? ItemSpec::STATUS_ENABLED,
                'app_id'       => $data['app_id'] ?? 0,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            // 如果传了 values 数组，批量创建规格值
            if (!empty($data['values']) && is_array($data['values'])) {
                $this->syncSpecItems($spec->id, $data['values'], $data['app_id'] ?? 0);
            }

            return $this->getDetail($spec->id);
        });
    }

    /**
     * 更新规格（支持附带规格值全量覆盖）
     */
    public function update($id, array $data)
    {
        return $this->transaction(function () use ($id, $data) {
            $spec = $this->repository->findOrFail($id);

            $updateData = [];
            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            if (isset($data['sort_order'])) {
                $updateData['sort_order'] = (int) $data['sort_order'];
            }
            if (isset($data['status'])) {
                $updateData['status'] = (int) $data['status'];
            }
            $updateData['updated_at'] = time();

            if (!empty($updateData)) {
                $spec->fill($updateData);
                $spec->save();
            }

            // 如果传了 values，全量覆盖规格值
            if (array_key_exists('values', $data)) {
                $this->syncSpecItems($spec->id, $data['values'] ?? [], $spec->app_id);
            }

            return $this->getDetail($spec->id);
        });
    }

    /**
     * 删除规格（连同规格值）
     */
    public function delete($id)
    {
        return $this->transaction(function () use ($id) {
            $spec = $this->repository->findOrFail($id);

            // 删除所有规格值
            $this->itemRepository->query()->where('spec_id', $id)->delete();

            return $spec->delete();
        });
    }

    /**
     * 更新规格状态
     */
    public function updateStatus($id, int $status): bool
    {
        $this->repository->update($id, [
            'status' => $status,
            'updated_at' => time(),
        ]);
        return true;
    }

    /**
     * 全量同步规格值（覆盖式）
     *
     * @param int $specId 规格ID
     * @param array $values 规格值数组，元素可为字符串或 ['name'=>'xxx','sort'=>0] 数组
     * @param int $appId
     */
    protected function syncSpecItems(int $specId, array $values, int $appId): void
    {
        // 先删除旧值
        $this->itemRepository->query()->where('spec_id', $specId)->delete();

        if (empty($values)) {
            return;
        }

        $now = time();
        $sort = 0;
        foreach ($values as $item) {
            if (is_string($item)) {
                $name = trim($item);
                if ($name === '') {
                    continue;
                }
            } else {
                $name = trim($item['name'] ?? $item['spec_item_name'] ?? '');
                if ($name === '') {
                    continue;
                }
                $sort = (int) ($item['sort'] ?? $sort);
            }

            $this->itemRepository->create([
                'spec_id'    => $specId,
                'item'       => $name,
                'order'      => $sort,
                'app_id'     => $appId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $sort++;
        }
    }

    /**
     * 格式化规格输出
     */
    protected function formatSpec($spec): array
    {
        return [
            'id'         => (string) $spec->id,
            'name'       => $spec->name,
            'sort_order' => (int) $spec->sort_order,
            'status'     => (int) $spec->status,
            'values'     => $spec->specItems ? $spec->specItems->map(function ($item) {
                return [
                    'id'    => (string) $item->id,
                    'name'  => $item->item,
                    'sort'  => (int) $item->order,
                ];
            })->toArray() : [],
            'created_at' => $spec->created_at,
        ];
    }
}
