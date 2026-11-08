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
use app\repository\ItemSpecStockLogRepository;
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
    use ItemQueryTrait;

    use ItemBatchTrait;

    use ItemInteractionTrait;

    use ItemReportTrait;

    use ItemProductSaveTrait;

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

        // 有「上架审核」模块（企业版）时，新建商品默认待审核，审核通过后再上架；
        // 开源版没有审核入口，若仍强制置 0 会导致商品永远无法上架，故直接采用提交值。
        if (class_exists('\\enterprise\\item_audit\\Model\\ItemAuditLog')) {
            $data['is_on_sale'] = 0;
            $data['status'] = 0;
        }

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

    public function delete($id): bool
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

    // ============================================================
    // 收藏 / 浏览 / 搜索
    // ============================================================

    // ============================================================
    // 报表 / 统计
    // ============================================================

    // ============================================================
    // 创建/更新订单 内部 - SKU / 规格 / 图片 / 标签
    // ============================================================

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
    /**
     * 按 ID 集合批量取商品，返回以 id 为键的数组（消除 N+1 查询）
     *
     * @param array<int, mixed> $ids
     * @return array<string, mixed>
     */
    public function findByIds(array $ids): array
    {
        return $this->repository->findByIds($ids);
    }

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
