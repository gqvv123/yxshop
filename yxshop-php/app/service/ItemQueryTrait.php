<?php

namespace app\service;
use app\model\Item;
use app\exception\BusinessException;
use Exception;

/**
 * ItemQueryTrait：从 ItemService.php 抽离的searchItems、findWithCategory、findWithRelations、getItemSkus、getItemList、getItemDetail、loadSkus、loadImages、resolveCategoryPath等方法。
 *
 * 拆分目的：控制单文件体积、聚焦单一职责；方法实现保持原样，行为完全等价。
 *
 * @package app\service
 */
trait ItemQueryTrait
{
    public function searchItems($appId, array $filters = [], $pageSize = 20)
    {
        return $this->repository->searchAdminItems((int) $appId, $filters, (int) $pageSize);
    }

    public function findWithCategory($id)
    {
        return $this->repository->findWithCategory($id);
    }

    public function findWithRelations($id)
    {
        $item = $this->repository->findWithAllRelations($id);
        if (!$item) {
            throw new BusinessException('商品不存在', 404);
        }

        $data = $item->toArray();
        $data['type']   = !empty($item->is_virtual) ? 'virtual' : 'physical';
        $data['video']  = $item->video_url;

        $data = array_merge($data, $this->resolveCategoryPath($item));
        $data['specs']   = $this->loadSpecs($item);
        $data['skus']    = $this->loadSkus($item);
        $data['images']  = $this->loadImages($item);
        $data['tag_ids'] = $item->tags->pluck('id')->toArray();

        // 产品参数（对应管理端「商品属性」）：属性名 + 属性值，供详情页参数表展示。
        // 原始 attrs 结构对 C 端无用，展开后剔除，避免响应体冗余。
        $data['attributes'] = $this->formatAttrParams($item);
        unset($data['attrs']);

        return $data;
    }

    /**
     * 商品属性 -> 详情页「产品参数」列表
     *
     * @param Item $item
     * @return array [{name, value}]
     */
    protected function formatAttrParams(Item $item): array
    {
        $rows = [];
        foreach ($item->attrs as $attr) {
            $name = $attr->attribute->name ?? '';
            $value = trim((string) ($attr->attr_value ?? ''));
            if ($name === '' || $value === '') {
                continue;
            }
            $rows[] = [
                'name'  => (string) $name,
                'value' => $value,
                'sort'  => (int) ($attr->attribute->sort ?? 0),
            ];
        }

        usort($rows, static fn($a, $b) => $a['sort'] <=> $b['sort']);
        return array_map(
            static fn($r) => ['name' => $r['name'], 'value' => $r['value']],
            $rows
        );
    }

    public function getItemSkus($id)
    {
        $item = $this->repository->findOrFail($id);
        return $this->loadSkus($item);
    }

    public function getItemList($params = [])
    {
        try {
            // 分类下钻：传入 cat_id 时取所有子分类 ID
            if (!empty($params['cat_id'])) {
                $category = $this->categoryService->getCategoryById($params['cat_id']);
                if ($category) {
                    $childIds = $category->getAllChildIds();
                    $params['category_id'] = !empty($childIds) ? $childIds : [$params['cat_id']];
                } else {
                    // 分类不存在时直接按 cat_id 作为 category_id 查询，避免崩溃
                    $params['category_id'] = $params['cat_id'];
                }
                unset($params['cat_id']);
            }

            // 记录搜索关键字
            if (!empty($params['keyword'])) {
                $this->recordSearch($params['keyword'], $params['user_id'] ?? 0, $params['app_id'] ?? 0);
            }

            return $this->repository->getListForUser($params);
        } catch (Exception $e) {
            $this->logError('获取商品列表失败', ['params' => $params, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getItemDetail($itemId, $userId = null)
    {
        try {
            $item = $this->repository->findOnSaleDetail($itemId);
            if (!$item) {
                throw new BusinessException('商品不存在或已下架', 404);
            }

            $item->addClick();
            if ($userId) {
                $this->recordView($itemId, $userId, $item->app_id);
            }
            return $item;
        } catch (Exception $e) {
            $this->logError('获取商品详情失败', ['item_id' => $itemId, 'user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function loadSkus(Item $item): array
    {
        return $this->specPriceRepository->getByItem($item->id)
            ->map(function ($sku) {
                $specValues = $sku->spec_values;
                if (is_string($specValues)) {
                    $decoded = json_decode($specValues, true);
                    $specValues = is_array($decoded) ? $decoded : [];
                }
                if (!is_array($specValues)) {
                    $specValues = [];
                }
                return [
                    'id'           => $sku->id,
                    'sku_code'     => $sku->sku,
                    'spec_values'  => $specValues,
                    'spec_label'   => is_array($specValues) && !empty($specValues) ? implode(' / ', array_values($specValues)) : ($sku->spec_key_name ?? ''),
                    'price'        => (float) $sku->price,
                    'cost_price'   => $sku->cost_price ? (float) $sku->cost_price : null,
                    'market_price' => $sku->market_price ? (float) $sku->market_price : null,
                    'stock'        => $sku->store_count,
                    'weight'       => $sku->weight ? (float) $sku->weight : null,
                    'image'        => $sku->image ?? '',
                ];
            })
            ->toArray();
    }

    protected function loadImages(Item $item): array
    {
        return $item->images->sortBy('sort')->map(function ($img) {
            return [
                'id'       => $img->id,
                'url'      => $img->url ?: $img->image_id,
                'is_cover' => (bool) $img->is_main,
                'sort'     => $img->sort,
            ];
        })->values()->toArray();
    }

    /**
     * 解析三级分类路径（按 category_id 自底向上回溯）
     */
    protected function resolveCategoryPath(Item $item): array
    {
        $result = [
            'category_id'        => $item->category_id,
            'sub_category_id'    => '',
            'third_category_id'  => '',
        ];
        if (!$item->category_id) {
            return $result;
        }

        try {
            $categories = $this->categoryRepository->getAllByApp((int) $item->app_id);

            $path = [];
            $currentId = $item->category_id;
            $visited = [];
            while ($currentId && !isset($visited[$currentId])) {
                $visited[$currentId] = true;
                $cat = $categories->get($currentId);
                if (!$cat) break;
                array_unshift($path, $cat);
                $currentId = $cat->parent_id ?? 0;
            }

            $levels = ['category_id', 'sub_category_id', 'third_category_id'];
            foreach ($path as $idx => $cat) {
                if ($idx < 3) {
                    $result[$levels[$idx]] = $cat->id;
                }
            }
        } catch (Exception $e) {
            // 容错：路径解析失败不阻塞详情接口
        }
        return $result;
    }
}
