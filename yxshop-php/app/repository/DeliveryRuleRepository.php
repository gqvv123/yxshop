<?php

namespace app\repository;

use app\model\DeliveryRule;

/**
 * 配送区域规则仓储
 *
 * 规则由 DeliveryService 在保存配送模板时整体提交（region / first / first_fee ...），
 * 早期版本的 rule_status / sort / rule_type / name 等列在现库中不存在，
 * 相关查询方法已移除，避免后台请求直接 SQL 报错。
 */
class DeliveryRuleRepository extends BaseRepository
{
    protected $model = DeliveryRule::class;
}
