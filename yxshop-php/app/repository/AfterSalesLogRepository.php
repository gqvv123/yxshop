<?php

namespace app\repository;

use app\model\AfterSalesLog;

/**
 * 售后操作日志仓储类
 */
class AfterSalesLogRepository extends BaseRepository
{
    protected $model = AfterSalesLog::class;

    /**
     * 写入操作日志
     */
    public function writeLog(
        int $afterSalesId,
        string $action,
        ?int $beforeStatus,
        ?int $afterStatus,
        int $actorId,
        ?string $actorName = null,
        ?string $remark = null,
        ?array $extra = null
    ): AfterSalesLog {
        return $this->create([
            'after_sales_id'  => $afterSalesId,
            'action'          => $action,
            'before_status'   => $beforeStatus,
            'after_status'    => $afterStatus,
            'actor_id'        => $actorId,
            'actor_name'      => $actorName,
            'remark'          => $remark,
            'extra'           => $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null,
            'created_at'      => time(),
        ]);
    }
}
