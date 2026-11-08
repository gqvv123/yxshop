<?php

namespace app\repository;

use app\model\ArticleLog;

/**
 * 文章操作日志仓储
 */
class ArticleLogRepository extends BaseRepository
{
    protected $model = ArticleLog::class;

    /**
     * 写入操作日志
     */
    public function writeLog(
        int $articleId,
        string $action,
        ?int $beforeStatus,
        ?int $afterStatus,
        int $actorId,
        ?string $actorName = null,
        ?string $remark = null
    ): ArticleLog {
        return $this->create([
            'article_id'    => $articleId,
            'action'        => $action,
            'before_status' => $beforeStatus,
            'after_status'  => $afterStatus,
            'actor_id'      => $actorId,
            'actor_name'    => $actorName,
            'remark'        => $remark,
            'created_at'    => time(),
        ]);
    }
}
