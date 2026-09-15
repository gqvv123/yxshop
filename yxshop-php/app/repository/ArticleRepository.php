<?php

namespace app\repository;

use app\model\Article;
use app\model\ArticleVersion;
use app\model\ArticleLog;
use support\Db;

/**
 * 文章 Repository
 *
 * 4 层架构：Repository 只负责数据访问（查询、创建、更新），
 * 状态校验、版本快照、操作日志等业务逻辑由 ArticleService 负责。
 */
class ArticleRepository extends BaseRepository
{
    protected $model = Article::class;

    /**
     * 获取文章列表
     */
    public function getArticles($appId = 0, $status = null)
    {
        $query = $this->query();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->orderBy('is_top', 'desc')
                    ->orderBy('sort', 'asc')
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * 获取已发布文章
     */
    public function getPublishedArticles($appId = 0)
    {
        return $this->getArticles($appId, Article::STATUS_PUBLISHED);
    }

    /**
     * 获取推荐文章
     */
    public function getRecommendedArticles($appId = 0, $limit = 10)
    {
        $query = $this->query();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->where('status', Article::STATUS_PUBLISHED)
                    ->where('is_recommend', 1)
                    ->orderBy('sort', 'asc')
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * 获取热门文章
     */
    public function getPopularArticles($appId = 0, $limit = 10)
    {
        $query = $this->query();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->where('status', Article::STATUS_PUBLISHED)
                    ->orderBy('views', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * 获取文章统计
     */
    public function getArticleStats($appId = 0)
    {
        $base = $this->query();

        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        return [
            'total'     => (clone $base)->count(),
            'published' => (clone $base)->where('status', Article::STATUS_PUBLISHED)->count(),
            'draft'     => (clone $base)->where('status', Article::STATUS_DRAFT)->count(),
            'hidden'    => (clone $base)->where('status', Article::STATUS_HIDDEN)->count(),
        ];
    }

    /**
     * 按条件分页查询文章列表（含 category 关联）
     */
    public function paginateForAdmin(array $params, int $page = 1, int $limit = 20): array
    {
        $query = $this->query()
            ->with(['category'])
            ->where('app_id', $params['app_id'] ?? 0);

        if (!empty($params['keyword'])) {
            $keyword = $params['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', '%' . $keyword . '%')
                  ->orWhere('content', 'like', '%' . $keyword . '%');
            });
        }

        if (!empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }

        $total  = (clone $query)->count();
        $offset = ($page - 1) * $limit;
        $items  = $query->orderBy('is_top', 'desc')
            ->orderBy('sort', 'asc')
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'data'  => $items,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    /**
     * 按 ID 查询文章（含 category 关联），不存在抛异常
     */
    public function findWithCategoryOrFail($id)
    {
        return $this->query()->with(['category'])->findOrFail($id);
    }

    /**
     * 获取详情（含版本历史 + 操作日志）
     */
    public function getDetail(int $id): ?Article
    {
        return $this->query()
            ->with(['category', 'versions', 'logs.actor'])
            ->find($id);
    }

    /**
     * 创建文章（纯数据操作，不含版本快照和日志）
     */
    public function createArticle(array $data): Article
    {
        $article = $this->model->newInstance($data);
        $article->save();
        return $article;
    }

    /**
     * 更新文章字段（纯数据操作，不含版本快照和日志）
     */
    public function updateArticle(Article $article, array $data): Article
    {
        $article->fill($data);
        $article->version = ($article->version ?? 1) + 1;
        $article->save();
        return $article;
    }

    /**
     * 更新文章状态（纯数据操作）
     */
    public function updateStatus(Article $article, int $status, array $extra = []): Article
    {
        $article->status = $status;
        foreach ($extra as $key => $value) {
            $article->{$key} = $value;
        }
        $article->version = $article->version + 1;
        $article->save();
        return $article;
    }

    /**
     * 软删除
     */
    public function delete($id): bool
    {
        $article = $this->findOrFail($id);
        return $article->softDelete();
    }
}
