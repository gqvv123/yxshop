<?php

namespace app\service;

use app\repository\UserContractRepository;
use app\model\UserContract;
use app\model\BaseModel;
use Exception;

/**
 * 用户合同服务
 *
 * @property UserContractRepository $repository
 */
class UserContractService extends BaseService
{
    public function __construct(?UserContractRepository $repository = null)
    {
        parent::__construct($repository ?? new UserContractRepository());
    }

    /**
     * 获取用户合同列表（分页）
     */
    public function getMyList(int $userId, int $page = 1, int $pageSize = 20)
    {
        $result = $this->repository->getByUserPaginated($userId, $page, $pageSize);
        $now = time();

        $items = $result->getCollection()->map(function ($c) use ($now) {
            $status = (int) $c->status;
            if ($status == UserContract::STATUS_SIGNED && $c->end_date > 0 && $c->end_date < $now) {
                $status = UserContract::STATUS_EXPIRED;
            }

            $fileUrl = $c->file_url ? BaseModel::resolveAssetUrl($c->file_url) : '';

            return [
                'id'            => (string) $c->id,
                'contract_no'   => $c->contract_no ?: '',
                'contract_name' => $c->contract_name,
                'contract_type' => (int) $c->contract_type,
                'type_text'     => $this->getTypeText($c->contract_type),
                'party_a'       => $c->party_a ?: '',
                'party_b'       => $c->party_b ?: '',
                'amount'        => (float) $c->amount,
                'start_date'    => (int) $c->start_date,
                'end_date'      => (int) $c->end_date,
                'start_text'    => $c->start_date > 0 ? date('Y-m-d', $c->start_date) : '',
                'end_text'      => $c->end_date > 0 ? date('Y-m-d', $c->end_date) : '',
                'file_url'      => $fileUrl,
                'status'        => $status,
                'status_text'   => $this->getStatusText($status),
                'remark'        => $c->remark ?: '',
                'signed_at'     => (int) $c->signed_at,
                'created_at'    => (int) $c->created_at,
            ];
        })->values();

        return [
            'data'         => $items,
            'total'        => $result->total(),
            'current_page' => $result->currentPage(),
            'last_page'    => $result->lastPage(),
        ];
    }

    /**
     * 获取合同详情
     */
    public function getDetail(int $id, int $userId): ?array
    {
        $contract = $this->repository->findByUser($id, $userId);
        if (!$contract) {
            return null;
        }

        $now = time();
        $status = (int) $contract->status;
        if ($status == UserContract::STATUS_SIGNED && $contract->end_date > 0 && $contract->end_date < $now) {
            $status = UserContract::STATUS_EXPIRED;
        }

        $fileUrl = $contract->file_url ? BaseModel::resolveAssetUrl($contract->file_url) : '';

        return [
            'id'            => (string) $contract->id,
            'contract_no'   => $contract->contract_no ?: '',
            'contract_name' => $contract->contract_name,
            'contract_type' => (int) $contract->contract_type,
            'type_text'     => $this->getTypeText($contract->contract_type),
            'party_a'       => $contract->party_a ?: '',
            'party_b'       => $contract->party_b ?: '',
            'amount'        => (float) $contract->amount,
            'start_date'    => (int) $contract->start_date,
            'end_date'      => (int) $contract->end_date,
            'start_text'    => $contract->start_date > 0 ? date('Y-m-d', $contract->start_date) : '',
            'end_text'      => $contract->end_date > 0 ? date('Y-m-d', $contract->end_date) : '',
            'file_url'      => $fileUrl,
            'status'        => $status,
            'status_text'   => $this->getStatusText($status),
            'remark'        => $contract->remark ?: '',
            'signed_at'     => (int) $contract->signed_at,
            'created_at'    => (int) $contract->created_at,
        ];
    }

    /**
     * 获取用户合同统计
     */
    public function getStats(int $userId): array
    {
        return $this->repository->getStatsByUser($userId);
    }

    private function getTypeText(int $type): string
    {
        $map = [
            UserContract::TYPE_PURCHASE => '采购合同',
            UserContract::TYPE_SALE     => '销售合同',
            UserContract::TYPE_SERVICE  => '服务合同',
            UserContract::TYPE_AGENCY   => '代理合同',
            UserContract::TYPE_OTHER    => '其他',
        ];
        return $map[$type] ?? '其他';
    }

    private function getStatusText(int $status): string
    {
        $map = [
            UserContract::STATUS_DRAFT      => '草稿',
            UserContract::STATUS_PENDING    => '待签署',
            UserContract::STATUS_SIGNED     => '已签署',
            UserContract::STATUS_EXPIRED    => '已过期',
            UserContract::STATUS_TERMINATED => '已终止',
        ];
        return $map[$status] ?? '未知';
    }
}
