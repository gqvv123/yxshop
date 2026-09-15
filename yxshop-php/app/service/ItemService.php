<?php

namespace app\service;

use app\repository\ItemRepository;
use app\repository\ItemFavoriteRepository;
use app\repository\ItemSearchRepository;
use app\repository\ItemViewRepository;
use app\repository\ItemSpecPriceRepository;
use app\repository\ItemAttrRepository;
use app\repository\ItemImageRepository;
use app\repository\ItemTagRelationRepository;
use app\repository\CategoryRepository;
use app\repository\ItemSpecPriceHistoryRepository;
use app\common\LicenseManager;
use app\repository\ItemSpecStockLogRepository;
use app\model\Item;
use app\exception\BusinessException;
use support\Db;
use Exception;

/**
 * 商品服务
 *
 * 4 层架构示范：所有数据访问通过对应 Repository 意图揭示方法，不在 Service 里写
 *   - $this->repository->query()->where(...)
 *   - Db::table('yxshop_xxx')
 *   - Model::where(...) / Model::create(...) 静态调用
 */
class ItemService extends BaseService
{
    protected CategoryService $categoryService;
    protected ItemFavoriteRepository $favoriteRepository;
    protected ItemSearchRepository $searchRepository;
    protected ItemViewRepository $viewRepository;
    protected ItemSpecPriceRepository $specPriceRepository;
    protected ItemAttrRepository $attrRepository;
    protected ItemImageRepository $imageRepository;
    protected ItemTagRelationRepository $tagRelationRepository;
    protected CategoryRepository $categoryRepository;
    protected mixed $auditLogRepository = null;
    protected ItemSpecPriceHistoryRepository $priceHistoryRepository;
    protected ItemSpecStockLogRepository $stockLogRepository;

    public function __construct(?ItemRepository $repository = null)
    {
        $repository = $repository ?? new ItemRepository();
        parent::__construct($repository);
        $this->categoryService = new CategoryService();
        $this->categoryRepository = new CategoryRepository();
        $this->favoriteRepository = new ItemFavoriteRepository();
        $this->searchRepository = new ItemSearchRepository();
        $this->viewRepository = new ItemViewRepository();
        $this->specPriceRepository = new ItemSpecPriceRepository(new \app\model\ItemSpecPrice());
        $this->attrRepository = new ItemAttrRepository(new \app\model\ItemAttr());
        $this->imageRepository = new ItemImageRepository(new \app\model\ItemImage());
        $this->tagRelationRepository = new ItemTagRelationRepository();
        // 商品审核日志（企业版功能，开源版不加载）
        if (class_exists('\\enterprise\\item_audit\\Repository\\ItemAuditLogRepository')) {
            $this->auditLogRepository = new \enterprise\item_audit\Repository\ItemAuditLogRepository();
        }
        $this->priceHistoryRepository = new ItemSpecPriceHistoryRepository();
        $this->stockLogRepository = new ItemSpecStockLogRepository();
    }

    // ============================================================
    // 后台 - 商品管理
    // ============================================================

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

        return $data;
    }

    public function getItemSkus($id)
    {
        $item = $this->repository->findOrFail($id);
        return $this->loadSkus($item);
    }

    public function create(array $data)
    {
        $this->validateNestedData($data);

        $specs   = $data['specs']   ?? [];
        $skus    = $data['skus']    ?? [];
        $images  = $data['images']  ?? [];
        $tagIds  = $data['tag_ids'] ?? [];

        unset($data['specs'], $data['skus'], $data['images'], $data['tag_ids']);

        $data = $this->resolveCategoryId($data);
        $data = $this->mapFieldsToDb($data);

        // 新建商品默认不上架，需审核通过后自动上架
        $data['is_on_sale'] = 0;
        $data['status'] = 0;

        $operatorId = $data['modifier_id'] ?? 0;
        $appId = $data['app_id'] ?? 0;

        return $this->transaction(function () use ($data, $specs, $skus, $images, $tagIds, $operatorId, $appId) {
            $item = $this->repository->create($data);

            if (!empty($specs))  $this->saveSpecs($item->id, $specs, $item->app_id);
            if (!empty($skus))   $this->saveSkus($item->id, $skus, $item->app_id);
            if (!empty($images)) $this->saveImages($item->id, $images, $item->app_id);
            if (!empty($tagIds)) $this->tagRelationRepository->setItemTags($item->id, $tagIds, $item->app_id);
            if (!empty($skus))   $this->calculateStock($item->id);

            // 自动创建审核记录：上架审核（企业版功能）
            if ($this->auditLogRepository && class_exists('\\enterprise\\item_audit\\Model\\ItemAuditLog')) {
                $this->auditLogRepository->create([
                    'item_id'      => $item->id,
                    'audit_type'   => \enterprise\item_audit\Model\ItemAuditLog::AUDIT_TYPE_LISTING,
                    'status'       => \enterprise\item_audit\Model\ItemAuditLog::STATUS_PENDING,
                    'submitter_id' => $operatorId,
                    'app_id'       => $item->app_id,
                ]);
            }

            return $item->fresh(['category', 'brand', 'images', 'skus']);
        });
    }

    public function update($id, array $data, int $operatorId = 0, string $operatorName = '')
    {
        $this->validateNestedData($data);

        $specs  = $data['specs']   ?? [];
        $skus   = $data['skus']    ?? [];
        $images = $data['images']  ?? [];
        $tagIds = $data['tag_ids'] ?? [];

        unset($data['specs'], $data['skus'], $data['images'], $data['tag_ids']);

        $data = $this->resolveCategoryId($data);
        $data = $this->mapFieldsToDb($data);

        return $this->transaction(function () use ($id, $data, $specs, $skus, $images, $tagIds, $operatorId, $operatorName) {
            $item = $this->repository->findOrFail($id);

            // 记录价格变动前的值
            $oldSalePrice = isset($data['sale_price']) ? (float) $item->sale_price : null;
            $oldPrice     = isset($data['price']) ? (float) $item->price : null;

            $item->update($data);

            // 价格变动写入历史
            $this->recordPriceChangeIfNeeded($item, $oldSalePrice, $oldPrice, $data, $operatorId, $operatorName);

            $this->replaceSpecs($item->id, $specs, $item->app_id);
            $this->replaceSkus($item->id, $skus, $item->app_id);
            $this->replaceImages($item->id, $images, $item->app_id);
            $this->tagRelationRepository->setItemTags($item->id, $tagIds, $item->app_id);

            if (!empty($skus)) {
                $this->calculateStock($item->id);
            }

            // 修改后需重新审核：自动创建修改审核记录（企业版功能）
            if ($this->auditLogRepository && class_exists('\\enterprise\\item_audit\\Model\\ItemAuditLog')) {
                $this->auditLogRepository->create([
                    'item_id'      => $item->id,
                    'audit_type'   => \enterprise\item_audit\Model\ItemAuditLog::AUDIT_TYPE_MODIFY,
                    'status'       => \enterprise\item_audit\Model\ItemAuditLog::STATUS_PENDING,
                    'submitter_id' => $operatorId,
                    'app_id'       => $item->app_id,
                ]);
            }

            return $item->fresh(['category', 'brand', 'images', 'skus']);
        });
    }

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

    public function delete($id)
    {
        $item = $this->repository->findOrFail($id);
        $item->deleted_at = time();
        $item->save();
        return true;
    }

    public function updateItemStatus($id, $status)
    {
        try {
            $this->logInfo('更新商品状态开始', ['id' => $id, 'status' => $status]);
            $item = $this->repository->findOrFail($id);
$item->is_on_sale = $status;
$item->status = $status;
$item->save();
            $this->logInfo('更新商品状态成功', ['id' => $id, 'status' => $status]);
            return $item;
        } catch (Exception $e) {
            $this->logError('更新商品状态失败', ['id' => $id, 'status' => $status, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    // ============================================================
    // 用户端 - 商品浏览
    // ============================================================

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

    public function getHotItems($limit = 10, $appId = 0)
    {
        try {
            return $this->repository->getHotItems((int) $limit, (int) $appId);
        } catch (Exception $e) {
            $this->logError('获取热门商品失败', ['limit' => $limit, 'app_id' => $appId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getRecommendedItems($limit = 10, $appId = 0)
    {
        try {
            return $this->repository->getRecommendedItems((int) $limit, (int) $appId);
        } catch (Exception $e) {
            $this->logError('获取推荐商品失败', ['limit' => $limit, 'app_id' => $appId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getNewItems($limit = 10, $appId = 0)
    {
        try {
            return $this->repository->getNewItems((int) $limit, (int) $appId);
        } catch (Exception $e) {
            $this->logError('获取新品失败', ['limit' => $limit, 'app_id' => $appId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    // ============================================================
    // 收藏 / 浏览 / 搜索
    // ============================================================

    public function addFavorite($itemId, $userId, $appId)
    {
        try {
            $this->logInfo('添加收藏开始', ['item_id' => $itemId, 'user_id' => $userId]);
            $this->repository->findOrFail($itemId);

            if ($this->favoriteRepository->isFavorited($userId, $itemId)) {
                throw new BusinessException('商品已收藏');
            }
            $this->favoriteRepository->addFavorite($userId, $itemId, $appId);

            $this->logInfo('添加收藏成功', ['item_id' => $itemId, 'user_id' => $userId]);
            return true;
        } catch (Exception $e) {
            $this->logError('添加收藏失败', ['item_id' => $itemId, 'user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function removeFavorite($itemId, $userId)
    {
        try {
            $this->favoriteRepository->removeFavorite($userId, $itemId);
            return true;
        } catch (Exception $e) {
            $this->logError('取消收藏失败', ['item_id' => $itemId, 'user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getUserFavorites($userId, $page = 1, $pageSize = 20)
    {
        try {
            return $this->favoriteRepository->getUserFavoritesPaginated((int) $userId, (int) $page, (int) $pageSize);
        } catch (Exception $e) {
            $this->logError('获取用户收藏列表失败', ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function isFavorited($itemId, $userId)
    {
        return $this->favoriteRepository->isFavorited($userId, $itemId);
    }

    private function recordSearch($keyword, $userId, $appId)
    {
        try {
            // 解码 URL 编码的搜索词（小程序端可能 encode 后传入）
            $keyword = urldecode($keyword);
            if ($keyword === '') {
                return;
            }

            $this->searchRepository->create([
                'user_id'       => $userId ?: 0,
                'keyword'       => $keyword,
                'app_id'        => $appId ?: 0,
                'ip'            => request()->getRemoteIp() ?? '',
                'result_count'  => 0,
                'created_at'    => time(),
            ]);
        } catch (Exception $e) {
            $this->logError('记录搜索失败', ['keyword' => $keyword, 'user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }

    private function recordView($itemId, $userId, $appId)
    {
        try {
            $this->viewRepository->create([
                'user_id'    => $userId,
                'item_id'    => $itemId,
                'app_id'     => $appId,
                'created_at' => time(),
            ]);
        } catch (Exception $e) {
            $this->logError('记录浏览失败', ['item_id' => $itemId, 'user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }

    // ============================================================
    // 报表 / 统计
    // ============================================================

    public function getItemStats($appId = 0): array
    {
        try {
            return $this->repository->getItemStats($appId);
        } catch (Exception $e) {
            $this->logError('获取商品统计失败', ['app_id' => $appId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getCount($appId): int
    {
        return $this->repository->countByApp((int) $appId);
    }

    public function getSoldOutItems($appId, $limit = 20)
    {
        return $this->repository->getSoldOutItems((int) $appId, (int) $limit);
    }

    public function getProductReport($appId): array
    {
        return $this->repository->getProductReport((int) $appId);
    }

    /**
     * 商品报表列表（按销量排序）
     */
    public function getProductReportList($appId): array
    {
        $query = $this->repository->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('total_sales', 'desc')
            ->limit(100)
            ->get(['id', 'name', 'total_sales', 'total_sales_amount', 'view_count'])
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'sales_count' => (int) $item->total_sales,
                    'sales_amount' => round((float) $item->total_sales_amount, 2),
                    'view_count' => (int) $item->view_count,
                ];
            })
            ->toArray();
    }

    public function getLatestItems($appId, $limit = 10)
    {
        $query = $this->repository->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('id', 'desc')->limit($limit)->get();
    }

    public function getProductByYear($appId): array
    {
        return $this->repository->getProductCountByYear((int) $appId);
    }

    public function getProductSellByMonth($appId): array
    {
        return $this->repository->getProductSellByMonth((int) $appId);
    }

    // ============================================================
    // 创建/更新订单 内部 - SKU / 规格 / 图片 / 标签
    // ============================================================

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

    protected function loadSpecs(Item $item): array
    {
        $attrs = $this->attrRepository->getByItem($item->id);

        $grouped = [];
        foreach ($attrs as $attr) {
            $parts = explode(':', $attr->attr_value, 2);
            $name  = $parts[0] ?? '';
            $value = $parts[1] ?? '';
            if (!isset($grouped[$name])) {
                $grouped[$name] = ['name' => $name, 'values' => []];
            }
            $grouped[$name]['values'][] = $value;
        }
        return array_values($grouped);
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

    public function getSummary(int $appId, string $period = 'month'): array
    {
        return [
            'onSaleCount' => $this->repository->getOnSaleCount($appId),
            'totalValue' => $this->repository->getTotalStockValue($appId),
            'stockAlert' => $this->repository->getStockAlertCount($appId),
            'onSaleTrend' => $this->repository->getOnSaleTrend($appId, $period),
            'valueTrend' => $this->repository->getValueTrend($appId, $period),
            'sparkline' => $this->repository->getSparklineData($appId, $period),
            'categoryContrib' => $this->repository->getCategoryContrib($appId),
            'trending' => $this->repository->getTrendingItems($appId),
        ];
    }

    public function batchPrice(array $ids, string $type, float $value, int $operatorId, string $operatorName = ''): array
    {
        $successCount = 0;
        $failedItems = [];

        return Db::connection()->transaction(function () use ($ids, $type, $value, $operatorId, $operatorName, &$successCount, &$failedItems) {
            foreach ($ids as $id) {
                $item = $this->repository->findOrFail($id);
                $oldPrice = (float) $item->sale_price;

                if ($type === 'fixed') {
                    $newPrice = $oldPrice + $value;
                } else {
                    $newPrice = $oldPrice * (1 + $value / 100);
                }

                if ($newPrice < 0.01) {
                    $failedItems[] = ['id' => $id, 'name' => $item->name, 'reason' => '改价后金额低于0.01'];
                    continue;
                }

                $item->sale_price = round($newPrice, 2);
                $item->save();

                $this->priceHistoryRepository->createLog([
                    'item_id' => $id,
                    'spec_id' => 0,
                    'old_price' => $oldPrice,
                    'new_price' => round($newPrice, 2),
                    'change_reason' => '批量改价',
                    'operator_id' => $operatorId,
                    'operator_name' => $operatorName,
                    'app_id' => $item->app_id,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);

                $successCount++;
            }

            return [
                'success_count' => $successCount,
                'failed_count' => count($failedItems),
                'failed_items' => $failedItems,
            ];
        });
    }

    public function adjustStock(int $itemId, string $type, int $quantity, string $remark, int $operatorId, string $operatorName = ''): array
    {
        $item = $this->repository->findOrFail($itemId);
        $beforeStock = (int) $item->stock;

        if ($type === 'in') {
            $afterStock = $beforeStock + $quantity;
            $changeType = 10;
            // 原子操作：increment 不会并发覆盖
            $affected = $this->repository->incrementStock($itemId, $quantity);
        } elseif ($type === 'out') {
            if ($quantity > $beforeStock) {
                throw new BusinessException('出库数量不能大于当前库存');
            }
            $afterStock = $beforeStock - $quantity;
            $changeType = 20;
            // 原子操作：WHERE stock >= quantity 条件下 decrement，防止并发超卖
            $affected = $this->repository->decrementStock($itemId, $quantity);
        } else {
            if ($quantity < 0) {
                throw new BusinessException('盘点库存不能为负数');
            }
            $afterStock = $quantity;
            $changeType = 30;
            // 盘点直接设置为指定值
            $affected = $this->repository->setStock($itemId, $afterStock);
        }

        if ($affected <= 0) {
            throw new BusinessException('库存调整失败，可能已被其他操作修改');
        }

        $item->refresh();
        $afterStock = (int) $item->stock;

        $now = time();
        $this->stockLogRepository->create([
            'item_id' => $itemId,
            'spec_id' => 0,
            'change_type' => $changeType,
            'change_quantity' => $type === 'check' ? ($afterStock - $beforeStock) : ($type === 'in' ? $quantity : -$quantity),
            'before_stock' => $beforeStock,
            'after_stock' => $afterStock,
            'change_reason' => $remark ?: ($type === 'check' ? '盘点' : ($type === 'in' ? '入库' : '出库')),
            'operator_id' => $operatorId,
            'operator_name' => $operatorName,
            'app_id' => $item->app_id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return ['id' => $itemId, 'stock' => $afterStock, 'change_type' => $type, 'before_stock' => $beforeStock, 'after_stock' => $afterStock];
    }

    public function batchAdjustStock(array $ids, string $type, int $quantity, string $remark, int $operatorId, string $operatorName = ''): array
    {
        $successCount = 0;
        $failedItems = [];
        foreach ($ids as $id) {
            try {
                $this->adjustStock($id, $type, $quantity, $remark, $operatorId, $operatorName);
                $successCount++;
            } catch (\Exception $e) {
                $item = $this->repository->find($id);
                $failedItems[] = ['id' => $id, 'name' => $item ? $item->name : '', 'reason' => $e->getMessage()];
            }
        }
        return ['success_count' => $successCount, 'failed_count' => count($failedItems), 'failed_items' => $failedItems];
    }

    public function duplicateItem(int $itemId, int $operatorId): array
    {
        return Db::connection()->transaction(function () use ($itemId, $operatorId) {
            $original = $this->repository->findWithAllRelations($itemId);
            if (!$original) {
                throw new BusinessException('商品不存在');
            }

            $newItem = $original->replicate();
            $newItem->name = $original->name . '(副本)';
            $newItem->is_on_sale = 0;
            $newItem->stock = 0;
            $newItem->total_sales = 0;
            $newItem->created_at = time();
            $newItem->updated_at = time();
            $newItem->save();

            if ($original->images) {
                foreach ($original->images as $img) {
                    $this->imageRepository->create([
                        'item_id' => $newItem->id,
                        'image_id' => $img->image_id,
                        'url' => $img->url,
                        'sort' => $img->sort,
                        'is_main' => $img->is_main,
                        'app_id' => $newItem->app_id,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);
                }
            }

            if ($original->skus) {
                foreach ($original->skus as $sku) {
                    $this->specPriceRepository->create([
                        'item_id' => $newItem->id,
                        'spec_key' => $sku->spec_key,
                        'spec_key_name' => $sku->spec_key_name,
                        'spec_values' => $sku->spec_values,
                        'price' => $sku->price,
                        'cost_price' => $sku->cost_price,
                        'market_price' => $sku->market_price,
                        'store_count' => 0,
                        'weight' => $sku->weight,
                        'sku' => $sku->sku,
                        'image' => $sku->image,
                        'app_id' => $newItem->app_id,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);
                }
            }

            return ['id' => $newItem->id, 'name' => $newItem->name, 'is_on_sale' => 0];
        });
    }

    public function getPriceHistory(int $itemId): array
    {
        $item = $this->repository->findOrFail($itemId);
        return ['list' => $this->repository->getPriceHistoryByItem($itemId)->toArray()];
    }

    public function exportItems(int $appId, array $filters, string $format = 'csv', array $ids = [])
    {
        $items = $this->repository->exportQuery($appId, $filters, $ids);
        if ($items->isEmpty()) {
            throw new BusinessException('当前无数据可导出');
        }

        $headers = ['商品名称', 'SKU', '分类', '品牌', '库存', '价格', '状态', '销量'];
        $rows = $items->map(function ($item) {
            return [
                $item->name,
                $item->skus && $item->skus->first() ? $item->skus->first()->sku : '',
                $item->category ? $item->category->name : '-',
                $item->brand ? $item->brand->name : '-',
                $item->stock,
                $item->sale_price,
                $item->is_on_sale ? '在售' : '下架',
                $item->total_sales,
            ];
        })->toArray();

        $filename = 'items_export_' . date('Ymd_His') . '.' . $format;

        if ($format === 'csv') {
            $output = "\xEF\xBB\xBF";
            $output .= implode(',', $headers) . "\n";
            foreach ($rows as $row) {
                $output .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', (string) ($v ?? '')) . '"', $row)) . "\n";
            }
            return response($output, 200, [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        return $items;
    }

    // ============================================================
    // 积分商城
    // ============================================================

    /**
     * 积分商品分页列表
     */
    public function getPointExchangeItems(int $appId = 0, int $page = 1, int $pageSize = 20, $ids = null)
    {
        $idArr = $ids ? (is_array($ids) ? $ids : explode(',', $ids)) : [];
        return $this->repository->getPointExchangeItems($appId, $page, $pageSize, $idArr);
    }

    /**
     * 积分商品列表（不分页，带limit）
     */
    public function getPointExchangeList(int $appId = 0, int $limit = 10)
    {
        return $this->repository->getPointExchangeList($appId, $limit);
    }

    /**
     * 查找积分商品（单个）
     */
    public function findPointExchangeItem($id)
    {
        return $this->repository->findPointExchangeItem($id);
    }

    /**
     * 扣减商品库存
     */
    public function deductStock($itemId, int $quantity): bool
    {
        return $this->repository->deductStock($itemId, $quantity);
    }

    /**
     * 获取商品选择列表（后台用，仅返回 id/name/sale_price）
     */
    public function getSelectList(int $appId, string $keyword = '', int $pageSize = 20)
    {
        $query = $this->repository->query()
            ->where('app_id', $appId)
            ->where('is_on_sale', 1)
            ->select(['id', 'name', 'sale_price']);

        if ($keyword) {
            $escaped = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $keyword);
            $query->where('name', 'like', "%{$escaped}%");
        }

        $maxLimit = 500;
        if ($pageSize >= $maxLimit || $pageSize === 999) {
            return $query->orderBy('id', 'desc')->limit($maxLimit)->get();
        }

        return $query->orderBy('id', 'desc')->paginate($pageSize);
    }

    /**
     * 获取商品主图URL
     */
    public function getMainImage($item): string
    {
        if (!$item) return '';
        return \app\model\BaseModel::resolveAssetUrl($item->getMainImageAttribute() ?: '');
    }

    /**
     * 获取相关推荐商品（C端）
     */
    public function getRelatedItems($itemId, int $limit = 10): array
    {
        $item = $this->findOrFail($itemId);

        $related = $this->repository->query()
            ->where('is_on_sale', 1)
            ->where('id', '!=', $itemId)
            ->where(function ($q) use ($item) {
                $q->where('category_id', $item->category_id)
                  ->orWhere('brand_id', $item->brand_id);
            })
            ->with(['images'])
            ->orderBy('total_sales', 'desc')
            ->limit($limit)
            ->get();

        return $related->map(function ($r) {
            return [
                'id'          => (string) $r->id,
                'name'        => $r->name,
                'subtitle'    => $r->subtitle ?? '',
                'sale_price'  => $r->sale_price,
                'price'       => $r->price,
                'image'       => $this->getMainImage($r),
                'total_sales' => $r->total_sales,
            ];
        })->toArray();
    }

    /**
     * 记录商品浏览（C端足迹）
     * 已有记录则 view_count + 1 并更新 created_at（最近浏览时间），否则新建
     */
    public function recordItemView(string $itemId, int $userId): void
    {
        $item = $this->findOrFail($itemId);

        $existing = $this->viewRepository->query()
            ->where('user_id', $userId)
            ->where('item_id', $itemId)
            ->first();

        if ($existing) {
            $this->viewRepository->query()
                ->where('id', $existing->id)
                ->update([
                    'view_count' => \support\Db::raw('view_count + 1'),
                    'created_at' => time(),
                ]);
        } else {
            $this->viewRepository->create([
                'user_id'    => $userId,
                'item_id'    => $itemId,
                'view_count' => 1,
                'app_id'     => $item->app_id,
                'created_at' => time(),
            ]);
        }
    }

    /**
     * 获取用户搜索历史（C端）
     */
    public function getSearchHistory(int $userId, int $limit = 10): array
    {
        return $this->searchRepository->query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($search) {
                return [
                    'id'           => $search->id,
                    'keyword'      => $search->keyword,
                    'result_count' => $search->result_count,
                    'created_at'   => $search->created_at,
                ];
            })->toArray();
    }

    /**
     * 清空用户搜索历史（C端）
     */
    public function clearSearchHistory(int $userId): void
    {
        $this->searchRepository->query()
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * 删除单条搜索历史（C端）
     */
    public function removeSearchHistory(string $id, int $userId): void
    {
        $this->searchRepository->query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * 获取分类下的品牌列表（C端）
     */
    public function getBrandsByCategory(string $categoryId): array
    {
        $brands = $this->repository->query()
            ->where('is_on_sale', 1)
            ->where('category_id', $categoryId)
            ->with('brand')
            ->whereNotNull('brand_id')
            ->where('brand_id', '>', 0)
            ->get()
            ->pluck('brand')
            ->filter()
            ->unique('id')
            ->map(function ($brand) {
                return [
                    'id'    => (string) $brand->id,
                    'name'  => $brand->name,
                    'logo'  => \app\model\BaseModel::resolveAssetUrl($brand->logo),
                    'desc'  => $brand->description ?? '',
                ];
            })
            ->values();

        return $brands->toArray();
    }
}
