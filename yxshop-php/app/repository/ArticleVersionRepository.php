<?php

namespace app\repository;

use app\model\ArticleVersion;

/**
 * 文章版本快照仓储
 */
class ArticleVersionRepository extends BaseRepository
{
    protected $model = ArticleVersion::class;

    /**
     * 根据文章ID和版本号查版本快照
     */
    public function findByArticleAndVersion(int $articleId, int $version): ?ArticleVersion
    {
        return $this->query()
            ->where('article_id', $articleId)
            ->where('version', $version)
            ->first();
    }

    /**
     * 获取文章的最新版本号
     */
    public function getMaxVersion(int $articleId): int
    {
        return $this->query()->where('article_id', $articleId)->max('version') ?? 0;
    }

    /**
     * 保存版本快照
     */
    public function saveVersion(array $data): ArticleVersion
    {
        return $this->create($data);
    }
}
