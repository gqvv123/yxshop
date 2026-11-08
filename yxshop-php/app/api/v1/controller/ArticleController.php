<?php

namespace app\api\v1\controller;

use app\service\ArticleService;
use support\Request;

/**
 * C 端控制器：帮助中心 / 商城公告。
 *
 * 对应管理端「内容管理 → 文章管理 / 文章分类」。
 * 小程序侧用于帮助中心、商城公告、常见问题。
 */
class ArticleController extends BaseController
{
    protected ArticleService $articleService;

    public function __construct()
    {
        $this->articleService = new ArticleService();
    }

    /**
     * 文章列表（只返回已发布的）
     *
     * GET /api/v1/article/list?page=1&page_size=10&category_id=1&keyword=发货
     */
    public function getList(Request $request)
    {
        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(50, max(1, (int) $request->get('page_size', 10)));
        $appId = (int) $request->get('app_id', 0);

        $params = [
            'keyword'     => trim((string) $request->get('keyword', '')),
            'category_id' => (int) $request->get('category_id', 0),
            'status'      => 1,           // 仅已发布
            'app_id'      => $appId,
        ];

        $result = $this->articleService->getArticleList($page, $pageSize, $params);
        // 仓储返回的 data 是 Eloquent Collection，需转成普通数组才能安全 unset 字段
        $list = $result['data'] ?? [];
        if ($list instanceof \Illuminate\Support\Collection) {
            $list = $list->toArray();
        } elseif (is_object($list) && method_exists($list, 'toArray')) {
            $list = $list->toArray();
        }
        if (!is_array($list)) {
            $list = [];
        }

        // 列表不返回正文，避免响应体过大
        foreach ($list as &$article) {
            unset($article['content']);
            $article['view_count'] = (int) ($article['view_count'] ?? $article['views'] ?? 0);
        }
        unset($article);

        return $this->success([
            'list'      => $list,
            'total'     => $result['total'] ?? count($list),
            'page'      => $page,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * 文章详情
     *
     * GET /api/v1/article/detail?id=1
     */
    public function getDetail(Request $request)
    {
        $id = (int) $request->get('id', 0);
        if (!$id) {
            return $this->error('文章ID不能为空');
        }

        $article = $this->articleService->getArticleById($id);
        if (!$article) {
            return $this->error('文章不存在', 404);
        }
        // 只允许查看已发布文章
        if ((int) ($article['status'] ?? 1) !== 1) {
            return $this->error('文章不存在', 404);
        }

        return $this->success($article);
    }

    /**
     * 文章分类列表（帮助中心导航）
     *
     * GET /api/v1/article/categories
     */
    public function getCategories(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        $categoryService = new \app\service\ArticleCategoryService();
        $categories = $categoryService->getCategoryTree($appId);

        return $this->success(['list' => $categories, 'total' => count($categories)]);
    }
}
