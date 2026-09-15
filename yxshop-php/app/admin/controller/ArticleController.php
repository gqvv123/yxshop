<?php

namespace app\admin\controller;

use support\Request;
use app\service\ArticleService;
use app\service\ArticleCategoryService;

class ArticleController extends BaseController
{
    protected $articleService;
    protected $categoryService;

    public function __construct()
    {
        parent::__construct();
        $this->articleService = new ArticleService();
        $this->categoryService = new ArticleCategoryService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');
        $categoryId = $request->get('category_id', 0);
        $status = $request->get('status', '');

        $articles = $this->articleService->getArticleList($page, $limit, [
            'keyword' => $keyword,
            'category_id' => $categoryId,
            'status' => $status,
            'app_id' => $appId
        ]);
        $categories = $this->categoryService->getCategoryTree($appId);
        return $this->success([
            'data' => $articles['data'] ?? $articles,
            'total' => $articles['total'] ?? 0,
            'page' => $page,
            'page_size' => $limit,
            'categories' => $categories,
        ]);
    }

    public function show(Request $request, $id)
    {
        $article = $this->articleService->getArticleById($id);
        if (!$article) {
            return $this->errorNotFound('文章不存在');
        }
        return $this->success($article);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $data['modifier_id'] = $this->admin['id'] ?? 0;
        $data['modifier_name'] = $this->admin['name'] ?? null;

        // 定时发布
        if (!empty($data['scheduled_at'])) {
            $data['scheduled_at'] = is_numeric($data['scheduled_at'])
                ? (int) $data['scheduled_at']
                : strtotime($data['scheduled_at']);
            if (!empty($data['status']) && $data['status'] == 1) {
                $data['status'] = 3; // 定时发布状态
            }
        }

        $result = $this->articleService->createArticle($data);
        if (!$result) {
            return $this->error('添加失败');
        }
        return $this->success($result, '添加成功');
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $data['modifier_id'] = $this->admin['id'] ?? 0;
        $data['modifier_name'] = $this->admin['name'] ?? null;

        // 定时发布
        if (array_key_exists('scheduled_at', $data) && !empty($data['scheduled_at'])) {
            $data['scheduled_at'] = is_numeric($data['scheduled_at'])
                ? (int) $data['scheduled_at']
                : strtotime($data['scheduled_at']);
        }

        $result = $this->articleService->updateArticle($id, $data);
        if (!$result) {
            return $this->error('更新失败');
        }
        return $this->success($result, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $result = $this->articleService->deleteArticle($id);
        if (!$result) {
            return $this->error('删除失败');
        }
        return $this->success(null, '删除成功');
    }

    /**
     * 发布
     */
    public function publish(Request $request, $id)
    {
        $actorId = $this->admin['id'] ?? 0;
        $actorName = $this->admin['name'] ?? null;
        try {
            $result = $this->articleService->publish((int) $id, $actorId, $actorName);
            return $this->success($result, '发布成功');
        } catch (\app\exception\BusinessException $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 下架
     */
    public function unpublish(Request $request, $id)
    {
        $actorId = $this->admin['id'] ?? 0;
        $actorName = $this->admin['name'] ?? null;
        try {
            $result = $this->articleService->unpublish((int) $id, $actorId, $actorName);
            return $this->success($result, '下架成功');
        } catch (\app\exception\BusinessException $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 快捷改状态（兼容旧）
     */
    public function updateStatus(Request $request, $id)
    {
        $status = $request->post('status');
        try {
            $result = $this->articleService->updateArticleStatus($id, $status);
            return $this->success(null, '更新成功');
        } catch (\app\exception\BusinessException $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 预览指定版本
     */
    public function previewVersion(Request $request, $id)
    {
        $version = (int) $request->get('version', 1);
        $result = $this->articleService->previewVersion((int) $id, $version);
        if (!$result) {
            return $this->error('版本不存在');
        }
        return $this->success($result);
    }

    /**
     * 恢复指定版本
     */
    public function restoreVersion(Request $request, $id)
    {
        $version = (int) $request->post('version', 1);
        $actorId = $this->admin['id'] ?? 0;
        $actorName = $this->admin['name'] ?? null;
        try {
            $result = $this->articleService->restoreVersion((int) $id, $version, $actorId, $actorName);
            return $this->success($result, '恢复成功');
        } catch (\app\exception\BusinessException $e) {
            return $this->error($e->getMessage());
        }
    }
}
