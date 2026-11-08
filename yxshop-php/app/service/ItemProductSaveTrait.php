<?php

namespace app\service;
use app\model\Item;
use app\exception\BusinessException;

/**
 * ItemProductSaveTrait：从 ItemService.php 抽离的recordPriceChangeIfNeeded、resolveCategoryId、mapFieldsToDb、validateNestedData、saveSpecs、saveSkus、saveImages、replaceSpecs、replaceSkus、replaceImages、calculateStock、loadSpecs等方法。
 *
 * 拆分目的：控制单文件体积、聚焦单一职责；方法实现保持原样，行为完全等价。
 *
 * @package app\service
 */
trait ItemProductSaveTrait
{
    /**
     * 商品价格变动时写入价格历史记录
     */
    protected function recordPriceChangeIfNeeded(Item $item, ?float $oldSalePrice, ?float $oldPrice, array $data, int $operatorId, string $operatorName): void
    {
        $now = time();
        $hasChange = false;

        if ($oldSalePrice !== null && isset($data['sale_price'])) {
            $newSalePrice = (float) $data['sale_price'];
            if (abs($newSalePrice - $oldSalePrice) >= 0.01) {
                $this->priceHistoryRepository->createLog([
                    'item_id'        => $item->id,
                    'spec_id'        => 0,
                    'old_price'      => $oldSalePrice,
                    'new_price'      => $newSalePrice,
                    'change_reason'  => '编辑商品改价',
                    'operator_id'    => $operatorId,
                    'operator_name'  => $operatorName,
                    'app_id'         => $item->app_id,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
                $hasChange = true;
            }
        }

        if (!$hasChange && $oldPrice !== null && isset($data['price'])) {
            $newPrice = (float) $data['price'];
            if (abs($newPrice - $oldPrice) >= 0.01) {
                $this->priceHistoryRepository->createLog([
                    'item_id'        => $item->id,
                    'spec_id'        => 0,
                    'old_price'      => $oldPrice,
                    'new_price'      => $newPrice,
                    'change_reason'  => '编辑商品原价',
                    'operator_id'    => $operatorId,
                    'operator_name'  => $operatorName,
                    'app_id'         => $item->app_id,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }
        }
    }

    protected function resolveCategoryId(array $data): array
    {
        if (!empty($data['third_category_id'])) {
            $data['category_id'] = $data['third_category_id'];
        } elseif (!empty($data['sub_category_id'])) {
            $data['category_id'] = $data['sub_category_id'];
        }
        unset($data['third_category_id'], $data['sub_category_id']);
        return $data;
    }

    protected function mapFieldsToDb(array $data): array
    {
        if (isset($data['type'])) {
            $data['is_physical'] = ($data['type'] === 'physical') ? 1 : 0;
            $data['is_virtual']  = ($data['type'] === 'virtual') ? 1 : 0;
            unset($data['type']);
        }
        if (isset($data['video'])) {
            $data['video_url'] = $data['video'];
            unset($data['video']);
        }
        if (isset($data['status'])) {
            $data['is_on_sale'] = (int) $data['status'];
        }

        $intDefaults = [
            'weight', 'length', 'width', 'height', 'stock', 'sort', 'valid_days',
            'stock_warning', 'points_exchange', 'shipping_template_id', 'member_level_id',
            'initial_sales', 'click', 'comment_count', 'total_sales', 'spec_type',
            'promotion_type', 'promotion_id', 'virtual_type', 'brand_id', 'category_id',
        ];
        foreach ($intDefaults as $field) {
            if (array_key_exists($field, $data) && ($data[$field] === null || $data[$field] === '')) {
                $data[$field] = 0;
            }
        }

        $stringDefaults = [
            'description', 'summary', 'subtitle', 'seo_title', 'seo_keywords', 'seo_description',
            'usage_note', 'unit', 'video_url', 'commission_amount', 'services',
        ];
        foreach ($stringDefaults as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null) {
                $data[$field] = '';
            }
        }

        $decimalDefaults = ['sale_price', 'price', 'cost_price', 'points_reward', 'discount'];
        foreach ($decimalDefaults as $field) {
            if (array_key_exists($field, $data) && ($data[$field] === null || $data[$field] === '')) {
                $data[$field] = 0;
            }
        }

        return $data;
    }

    protected function validateNestedData(array $data): void
    {
        if (isset($data['skus']) && is_array($data['skus'])) {
            if (count($data['skus']) > 200) {
                throw new BusinessException('SKU数量不能超过200');
            }
            foreach ($data['skus'] as $sku) {
                if (isset($sku['price']) && $sku['price'] < 0) {
                    throw new BusinessException('SKU价格不能小于0');
                }
                if (isset($sku['stock']) && $sku['stock'] < 0) {
                    throw new BusinessException('SKU库存不能小于0');
                }
            }
        }
        if (isset($data['images']) && is_array($data['images'])) {
            foreach ($data['images'] as $img) {
                if (empty($img['url'])) {
                    throw new BusinessException('图片URL不能为空');
                }
            }
        }
        if (isset($data['specs']) && is_array($data['specs']) && !empty($data['specs'])) {
            foreach ($data['specs'] as $spec) {
                if (empty($spec['name'])) {
                    throw new BusinessException('规格名称不能为空');
                }
            }
        }
    }

    protected function saveSpecs($itemId, array $specs, $appId): void
    {
        $now = time();
        foreach ($specs as $spec) {
            $specValues = is_array($spec['values']) ? $spec['values'] : explode(',', $spec['values']);
            foreach ($specValues as $value) {
                $this->attrRepository->create([
                    'item_id'    => $itemId,
                    'attr_id'    => 0,
                    'attr_value' => $spec['name'] . ':' . $value,
                    'attr_price' => 0,
                    'app_id'     => $appId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    protected function saveSkus($itemId, array $skus, $appId): void
    {
        $now = time();
        foreach ($skus as $sku) {
            $specValues = $sku['spec_values'] ?? [];
            $specKey = md5(json_encode($specValues));
            $specKeyName = is_array($specValues) ? implode(' ', $specValues) : (string) $specValues;

            $this->specPriceRepository->create([
                'item_id'       => $itemId,
                'spec_key'      => $specKey,
                'spec_key_name' => $specKeyName,
                'spec_values'   => $specValues,
                'price'         => $sku['price'] ?? 0,
                'cost_price'    => $sku['cost_price'] ?? null,
                'market_price'  => $sku['market_price'] ?? null,
                'store_count'   => $sku['stock'] ?? 0,
                'weight'        => $sku['weight'] ?? null,
                'sku'           => $sku['sku_code'] ?? '',
                'image'         => $sku['image'] ?? '',
                'app_id'        => $appId,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }
    }

    protected function saveImages($itemId, array $images, $appId): void
    {
        $now = time();
        foreach ($images as $img) {
            $this->imageRepository->create([
                'item_id'    => $itemId,
                'image_id'   => is_numeric($img['url'] ?? '') ? $img['url'] : '',
                'url'        => $img['url'] ?? '',
                'is_main'    => !empty($img['is_cover']) ? 1 : 0,
                'sort'       => $img['sort'] ?? 0,
                'app_id'     => $appId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    protected function replaceSpecs($itemId, array $specs, $appId): void
    {
        $this->attrRepository->deleteByItem($itemId);
        if (!empty($specs)) {
            $this->saveSpecs($itemId, $specs, $appId);
        }
    }

    protected function replaceSkus($itemId, array $skus, $appId): void
    {
        $this->specPriceRepository->deleteByItem($itemId);
        if (!empty($skus)) {
            $this->saveSkus($itemId, $skus, $appId);
        }
    }

    protected function replaceImages($itemId, array $images, $appId): void
    {
        $this->imageRepository->deleteByItem($itemId);
        if (!empty($images)) {
            $this->saveImages($itemId, $images, $appId);
        }
    }

    protected function calculateStock($itemId): void
    {
        $totalStock = $this->specPriceRepository->sumStockByItem($itemId);
        $this->repository->updateStockById($itemId, $totalStock);
    }

    /**
     * 商品规格参数（详情页「规格参数」用）
     *
     * 属性名以 yxshop_item_attributes.name 为准；仅当属性定义缺失时才回退到
     * 「把 attr_value 按冒号拆开」的老格式，避免把纯值当规格名（历史上会导致
     * 详情页出现 "精选纸品:" 这种 label 是值、value 为空的错行）。
     *
     * @param Item $item
     * @return array [{name, values[]}]
     */
    protected function loadSpecs(Item $item): array
    {
        $attrs = $this->attrRepository->getByItem($item->id);

        $grouped = [];
        foreach ($attrs as $attr) {
            $raw      = trim((string) ($attr->attr_value ?? ''));
            $attrName = trim((string) ($attr->attribute->name ?? ''));

            if ($attrName !== '') {
                $name  = $attrName;
                $value = $raw;
                // 兼容 "名称:值" 的老数据（前缀与该属性名一致时剥掉前缀）
                if ($raw !== '' && strpos($raw, $attrName . ':') === 0) {
                    $value = substr($raw, strlen($attrName) + 1);
                }
            } elseif (strpos($raw, ':') !== false) {
                [$name, $value] = explode(':', $raw, 2);
                $name  = trim($name);
                $value = trim($value);
            } else {
                // 既无属性定义也没有分隔符，无法判断哪部分是名字，跳过而不是产出错行
                continue;
            }

            $value = trim($value);
            if ($name === '' || $value === '') {
                continue;
            }

            if (!isset($grouped[$name])) {
                $grouped[$name] = ['name' => $name, 'values' => []];
            }
            $grouped[$name]['values'][] = $value;
        }

        return array_values($grouped);
    }
}
