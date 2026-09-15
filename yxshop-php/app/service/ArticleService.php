<?php

namespace app\service;

use app\repository\ArticleRepository;
use app\repository\ArticleVersionRepository;
use app\repository\ArticleLogRepository;
use app\model\Article;
use app\exception\BusinessException;
use support\Db;
use Exception;

/**
 * 文章服务类
 *
 * 4 层架构：状态校验、版本快照、操作日志等业务逻辑在 Service 层；
 * Repository 只负责纯数据操作。
 *
 * @property ArticleRepository $repository
 */
class ArticleService extends BaseService
{
    protected ArticleVersionRepository $versionRepo;
    protected ArticleLogRepository $logRepo;

    public function __construct(?ArticleRepository $repository = null)
    {
        parent::__construct($repository ?? new ArticleRepository());
        $this->versionRepo = new ArticleVersionRepository();
        $this->logRepo = new ArticleLogRepository();
    }

    /**
     * 获取文章列表
     */
    public function getArticleList($page = 1, $limit = 20, $params = [])
    {
        try {
            $this->logInfo('获取文章列表开始', ['page' => $page, 'limit' => $limit, 'params' => $params]);
            $result = $this->repository->paginateForAdmin($params, (int) $page, (int) $limit);
            $this->logInfo('获取文章列表成功', ['total' => $result['total']]);
            return $result;
        } catch (Exception $e) {
            $this->logError('获取文章列表失败', ['page' => $page, 'limit' => $limit, 'params' => $params, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 获取文章详情（含版本历史 + 操作日志）
     */
    public function getArticleById($id)
    {
        try {
            return $this->repository->getDetail((int) $id);
        } catch (Exception $e) {
            $this->logError('获取文章详情失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 创建文章（事务 + 版本快照 + 操作日志）
     */
    public function createArticle(array $data)
    {
        try {
            $this->logInfo('创建文章开始', ['data' => $data]);

            $article = Db::transaction(function () use ($data) {
                $article = $this->repository->createArticle($data);

                $this->saveVersion($article, $data['modifier_id'] ?? 0, $data['modifier_name'] ?? null, '创建文章');

                $this->logRepo->writeLog(
                    (int) $article->id,
                    Article::ACTION_CREATE,
                    null,
                    $article->status,
                    $data['modifier_id'] ?? 0,
                    $data['modifier_name'] ?? null,
                    '创建文章'
                );

                return $article;
            });

            $this->logInfo('创建文章成功', ['article_id' => $article->id]);
            return $article;
        } catch (Exception $e) {
            $this->logError('创建文章失败', ['data' => $data, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 更新文章（事务 + 版本快照 + 操作日志）
     */
    public function updateArticle($id, array $data)
    {
        try {
            $this->logInfo('更新文章开始', ['id' => $id, 'data' => $data]);

            $article = Db::transaction(function () use ($id, $data) {
                $article = $this->repository->findOrFail($id);
                $beforeStatus = $article->status;

                $article = $this->repository->updateArticle($article, $data);

                $this->saveVersion($article, $data['modifier_id'] ?? null, $data['modifier_name'] ?? null, $data['change_summary'] ?? null);

                if (isset($data['status']) && $data['status'] !== $beforeStatus) {
                    $action = $this->statusToAction((int) $data['status']);
                    $this->logRepo->writeLog(
                        (int) $id,
                        $action,
                        $beforeStatus,
                        (int) $data['status'],
                        $data['modifier_id'] ?? 0,
                        $data['modifier_name'] ?? null
                    );
                } else {
                    $this->logRepo->writeLog(
                        (int) $id,
                        Article::ACTION_UPDATE,
                        $beforeStatus,
                        $article->status,
                        $data['modifier_id'] ?? 0,
                        $data['modifier_name'] ?? null,
                        '修改内容'
                    );
                }

                return $article;
            });

            $this->logInfo('更新文章成功', ['article_id' => $id]);
            return $article;
        } catch (Exception $e) {
            $this->logError('更新文章失败', ['id' => $id, 'data' => $data, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 发布文章（状态校验 + 事务 + 日志）
     */
    public function publish(int $id, int $actorId, ?string $actorName = null): Article
    {
        try {
            $this->logInfo('发布文章开始', ['id' => $id, 'actor_id' => $actorId]);

            $article = $this->repository->findOrFail($id);

            if (!$article->canTransitionTo(Article::STATUS_PUBLISHED)) {
                throw new BusinessException('当前状态不允许发布：' . $article->getStatusText());
            }

            $beforeStatus = $article->status;

            $result = Db::transaction(function () use ($article, $beforeStatus, $actorId, $actorName) {
                $article = $this->repository->updateStatus($article, Article::STATUS_PUBLISHED, [
                    'published_at' => time(),
                    'scheduled_at' => null,
                ]);

                $this->logRepo->writeLog(
                    (int) $article->id,
                    Article::ACTION_PUBLISH,
                    $beforeStatus,
                    Article::STATUS_PUBLISHED,
                    $actorId,
                    $actorName
                );

                return $article;
            });

            $this->logInfo('发布文章成功', ['id' => $id]);
            return $result;
        } catch (BusinessException $e) {
            $this->logWarning('发布文章业务异常', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (Exception $e) {
            $this->logError('发布文章失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 下架文章（状态校验 + 事务 + 日志）
     */
    public function unpublish(int $id, int $actorId, ?string $actorName = null): Article
    {
        try {
            $this->logInfo('下架文章开始', ['id' => $id, 'actor_id' => $actorId]);

            $article = $this->repository->findOrFail($id);

            if (!$article->canTransitionTo(Article::STATUS_HIDDEN)) {
                throw new BusinessException('当前状态下架：' . $article->getStatusText());
            }

            $beforeStatus = $article->status;

            $result = Db::transaction(function () use ($article, $beforeStatus, $actorId, $actorName) {
                $article = $this->repository->updateStatus($article, Article::STATUS_HIDDEN);

                $this->logRepo->writeLog(
                    (int) $article->id,
                    Article::ACTION_UNPUBLISH,
                    $beforeStatus,
                    Article::STATUS_HIDDEN,
                    $actorId,
                    $actorName
                );

                return $article;
            });

            $this->logInfo('下架文章成功', ['id' => $id]);
            return $result;
        } catch (BusinessException $e) {
            $this->logWarning('下架文章业务异常', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (Exception $e) {
            $this->logError('下架文章失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 删除文章（软删除 + 日志）
     */
    public function deleteArticle($id)
    {
        try {
            $this->logInfo('删除文章开始', ['id' => $id]);

            $result = Db::transaction(function () use ($id) {
                $article = $this->repository->findOrFail($id);

                $this->logRepo->writeLog(
                    (int) $id,
                    Article::ACTION_DELETE,
                    $article->status,
                    null,
                    0,
                    null
                );

                return $article->softDelete();
            });

            $this->logInfo('删除文章成功', ['article_id' => $id]);
            return $result;
        } catch (Exception $e) {
            $this->logError('删除文章失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 更新文章状态（兼容旧调用）
     */
    public function updateArticleStatus($id, $status)
    {
        try {
            $this->logInfo('更新文章状态开始', ['id' => $id, 'status' => $status]);

            if ((int) $status === Article::STATUS_PUBLISHED) {
                $article = $this->repository->findOrFail($id);
                $article = $this->publish($id, $article->modifier_id ?? 0, $article->modifier_name);
            } elseif ((int) $status === Article::STATUS_HIDDEN) {
                $article = $this->repository->findOrFail($id);
                $article = $this->unpublish($id, $article->modifier_id ?? 0, $article->modifier_name);
            } else {
                $article = $this->repository->findOrFail($id);
                $article->status = $status;
                $article->save();
            }

            $this->logInfo('更新文章状态成功', ['id' => $id, 'status' => $status]);
            return $article;
        } catch (Exception $e) {
            $this->logError('更新文章状态失败', ['id' => $id, 'status' => $status, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 获取文章统计
     */
    public function getArticleStats($appId = 0)
    {
        try {
            return $this->repository->getArticleStats($appId);
        } catch (Exception $e) {
            $this->logError('获取文章统计失败', ['app_id' => $appId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 预览文章（获取指定版本内容）
     */
    public function previewVersion(int $articleId, int $version): ?array
    {
        $versionRecord = $this->versionRepo->findByArticleAndVersion($articleId, $version);
        return $versionRecord ? $versionRecord->toArray() : null;
    }

    /**
     * 恢复指定版本
     */
    public function restoreVersion(int $articleId, int $version, int $actorId, ?string $actorName = null): Article
    {
        $versionRecord = $this->versionRepo->findByArticleAndVersion($articleId, $version);

        if (!$versionRecord) {
            throw new BusinessException('版本不存在');
        }

        return $this->updateArticle($articleId, [
            'title'        => $versionRecord->title,
            'subtitle'     => $versionRecord->subtitle,
            'content'      => $versionRecord->content,
            'excerpt'      => $versionRecord->excerpt,
            'cover_image'  => $versionRecord->cover_image,
            'category_id'  => $versionRecord->category_id,
            'author'       => $versionRecord->author,
            'modifier_id'  => $actorId,
            'modifier_name'=> $actorName,
            'change_summary' => "恢复至 v{$version} 版本",
        ]);
    }

    // ============================================================
    // 内部方法
    // ============================================================

    /**
     * 保存内容版本快照
     */
    private function saveVersion(Article $article, ?int $modifierId, ?string $modifierName, ?string $changeSummary): void
    {
        $latestVersion = $this->versionRepo->getMaxVersion((int) $article->id);

        $this->versionRepo->saveVersion([
            'article_id'    => $article->id,
            'version'       => $latestVersion + 1,
            'title'         => $article->title,
            'subtitle'      => $article->subtitle,
            'content'       => $article->content,
            'excerpt'       => $article->excerpt,
            'cover_image'   => $article->cover_image,
            'category_id'   => $article->category_id,
            'author'        => $article->author,
            'modifier_id'   => $modifierId,
            'modifier_name' => $modifierName,
            'change_summary' => $changeSummary,
        ]);
    }

    /**
     * 根据状态值推算操作动作
     */
    private function statusToAction(int $status): string
    {
        return [
            Article::STATUS_PUBLISHED => Article::ACTION_PUBLISH,
            Article::STATUS_HIDDEN    => Article::ACTION_UNPUBLISH,
        ][$status] ?? Article::ACTION_UPDATE;
    }
}
