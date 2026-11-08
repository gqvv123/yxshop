<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\ItemService;
use app\service\CategoryService;

class ItemController extends BaseController
{
    protected $itemService;
    protected $categoryService;

    public function __construct()
    {
        $this->itemService = new ItemService();
        $this->categoryService = new CategoryService();
    }

    public function getList(Request $request)
    {
        $params = $request->all();
        $params['user_id'] = $this->getCurrentUserId($request);
        $params['app_id'] = $request->get('app_id', 0);

        $result = $this->itemService->getItemList($params);

        return $this->success($result);
    }

    public function getDetail(Request $request)
    {
        $itemId = $request->get('id');
        $userId = $this->getCurrentUserId($request);

        if (!$itemId) {
            return $this->error('商品ID不能为空');
        }

        // 使用全关联查询（含 category/brand/images/skus/attrs）
        $item = $this->itemService->findWithRelations($itemId);

        // 增补 C 端专属字段
        $item['is_favorited'] = $userId ? $this->itemService->isFavorited($itemId, $userId) : false;

        // 评价摘要
        $commentService = new \app\service\CommentService();
        $item['review_summary'] = $commentService->getItemReviewSummary($itemId);

        return $this->success($item);
    }

    /**
     * 获取商品评价列表
     *
     * GET /api/v1/item/reviews?id=1&page=1&page_size=10&filter=all|good|medium|bad|with_image
     */
    public function getReviews(Request $request)
    {
        $itemId = $request->get('id', '');
        if (empty($itemId)) {
            return $this->error('商品ID不能为空');
        }

        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(50, max(1, (int) $request->get('page_size', 10)));
        $filter = $request->get('filter', 'all');

        try {
            $commentService = new \app\service\CommentService();
            $result = $commentService->getItemCommentsPaginated($itemId, $filter, $page, $pageSize);
            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取相关推荐商品
     *
     * GET /api/v1/item/related?id=1&limit=10
     */
    public function getRelated(Request $request)
    {
        $itemId = $request->get('id', '');
        $limit = min(20, max(1, (int) $request->get('limit', 10)));

        if (empty($itemId)) {
            return $this->error('商品ID不能为空');
        }

        try {
            $related = $this->itemService->getRelatedItems($itemId, $limit);
            return $this->success($related);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 记录商品浏览（足迹）
     *
     * POST /api/v1/item/view  item_id=1
     */
    public function view(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $itemId = $request->post('item_id', '');
        if (empty($itemId)) {
            return $this->error('商品ID不能为空');
        }

        try {
            $this->itemService->recordItemView($itemId, $userId);
            return $this->success(null, '已记录');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取商品咨询/问答列表
     *
     * GET /api/v1/item/consultations?id=1&page=1&page_size=10
     */
    public function getConsultations(Request $request)
    {
        $itemId = $request->get('id', '');
        if (empty($itemId)) {
            return $this->error('商品ID不能为空');
        }

        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(50, max(1, (int) $request->get('page_size', 10)));

        try {
            $consultationService = new \app\service\ItemConsultationService();
            $all = $consultationService->getByItem($itemId);

            // 分页处理（getByItem 返回 Collection，手动分页）
            $total = $all->count();
            $offset = ($page - 1) * $pageSize;
            $list = $all->slice($offset, $pageSize)->map(function ($c) {
                return [
                    'id'            => (string) $c->id,
                    'content'       => $c->content,
                    'reply_content' => $c->reply_content ?? '',
                    'status'        => $c->status,
                    'status_text'   => $c->status == \app\model\ItemConsultation::STATUS_REPLIED ? '已回复' : '待回复',
                    'consult_type'  => $c->consult_type,
                    'is_anonymous'  => (int) $c->is_anonymous,
                    'user_nickname' => $c->is_anonymous ? '匿名用户' : ($c->user->nickname ?? '用户' . $c->user_id),
                    'user_avatar'   => $c->is_anonymous ? '' : \app\model\BaseModel::resolveAssetUrl($c->user->avatar_url),
                    'created_at'    => $c->created_at,
                ];
            })->values();

            return $this->success([
                'list'      => $list,
                'total'     => $total,
                'page'      => $page,
                'page_size' => $pageSize,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 提交商品咨询
     *
     * POST /api/v1/item/consult
     * item_id=1, content=xxx, consult_type=1, is_anonymous=0
     */
    public function consult(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $itemId = $request->post('item_id', '');
        $content = trim($request->post('content', ''));
        $consultType = (int) $request->post('consult_type', \app\model\ItemConsultation::CONSULT_TYPE_ITEM);
        $isAnonymous = (int) $request->post('is_anonymous', 0);

        if (empty($itemId)) {
            return $this->error('商品ID不能为空');
        }
        if ($content === '') {
            return $this->error('咨询内容不能为空');
        }

        try {
            $item = $this->itemService->findOrFail($itemId);

            $consultationService = new \app\service\ItemConsultationService();
            $consultation = $consultationService->create([
                'item_id'       => $itemId,
                'user_id'       => $userId,
                'consult_type'  => $consultType,
                'content'       => $content,
                'is_anonymous'  => $isAnonymous,
                'status'        => \app\model\ItemConsultation::STATUS_PENDING,
                'app_id'        => $item->app_id,
            ]);

            return $this->success(['id' => $consultation->id], '提交成功，我们会尽快回复');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function getHot(Request $request)
    {
        $limit = $request->get('limit', 10);
        $appId = $request->get('app_id', 0);

        $result = $this->itemService->getHotItems($limit, $appId);

        return $this->success($result);
    }

    public function getRecommended(Request $request)
    {
        $limit = $request->get('limit', 10);
        $appId = $request->get('app_id', 0);

        $result = $this->itemService->getRecommendedItems($limit, $appId);

        return $this->success($result);
    }

    public function getNew(Request $request)
    {
        $limit = $request->get('limit', 10);
        $appId = $request->get('app_id', 0);

        $result = $this->itemService->getNewItems($limit, $appId);

        return $this->success($result);
    }

    /**
     * 获取商品分类树
     */
    public function getCategories(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        $tree = $this->categoryService->getCategoryTree($appId);
        return $this->success($tree);
    }

    public function search(Request $request)
    {
        $params = $request->all();
        $params['user_id'] = $this->getCurrentUserId($request);
        $params['app_id'] = $request->get('app_id', 0);

        $result = $this->itemService->getItemList($params);

        return $this->success($result);
    }
}
