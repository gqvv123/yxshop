<?php

namespace app\service;

use app\exception\BusinessException;
use app\model\InvoiceHeader;
use app\model\InvoiceRecord;
use app\repository\InvoiceRepository;

/**
 * 发票业务服务（C 端）。
 *
 * 对应管理端「财务管理 → 发票管理」的用户侧能力：
 * 维护发票抬头、按订单申请开票、查询开票记录。
 *
 * @package app\service
 */
class InvoiceService extends BaseService
{
    // 注意：基类已定义 protected $repository，PHP 8.5 不允许子类再给它加类型，
    // 因此这里不再重复声明，直接复用基类的属性。

    public function __construct(?InvoiceRepository $repository = null)
    {
        parent::__construct($repository ?? new InvoiceRepository());
    }

    /**
     * 发票抬头列表
     */
    public function getHeaderList(int $userId, int $appId = 0): array
    {
        return $this->repository->getUserHeaders($userId, $appId);
    }

    /**
     * 保存发票抬头
     *
     * @return array<string, mixed>
     */
    public function saveHeader(int $userId, array $data, int $appId = 0): array
    {
        $headerName = trim((string) ($data['header_name'] ?? ''));
        if ($headerName === '') {
            throw new BusinessException('请填写抬头名称');
        }

        $invoiceType = (int) ($data['invoice_type'] ?? InvoiceHeader::TYPE_PERSONAL);
        if (!in_array($invoiceType, [InvoiceHeader::TYPE_PERSONAL, InvoiceHeader::TYPE_COMPANY], true)) {
            $invoiceType = InvoiceHeader::TYPE_PERSONAL;
        }

        // 企业抬头必须要有税号
        if ($invoiceType === InvoiceHeader::TYPE_COMPANY && trim((string) ($data['tax_no'] ?? '')) === '') {
            throw new BusinessException('企业抬头必须填写纳税人识别号');
        }

        $isDefault = (int) ($data['is_default'] ?? 0) === 1 ? 1 : 0;
        $id = (int) ($data['id'] ?? 0);

        $fill = [
            'user_id'      => $userId,
            'header_name'  => $headerName,
            'invoice_type' => $invoiceType,
            'tax_no'       => trim((string) ($data['tax_no'] ?? '')),
            'reg_address'  => trim((string) ($data['reg_address'] ?? '')),
            'reg_phone'    => trim((string) ($data['reg_phone'] ?? '')),
            'bank_name'    => trim((string) ($data['bank_name'] ?? '')),
            'bank_account' => trim((string) ($data['bank_account'] ?? '')),
            'is_default'   => $isDefault,
            'app_id'       => $appId,
        ];

        if ($id > 0) {
            $header = $this->repository->findOrFail($id);
            if ((int) $header->user_id !== $userId) {
                throw new BusinessException('无权操作该发票抬头');
            }
            $header->fill($fill);
            $header->save();
        } else {
            // 首个抬头默认设为默认抬头
            if (!$this->repository->getUserHeaders($userId, $appId)) {
                $fill['is_default'] = 1;
            }
            $header = $this->repository->create($fill);
        }

        if ($isDefault) {
            $this->repository->clearDefaultFlag($userId);
            $header->is_default = 1;
            $header->save();
        }

        return (array) $header->toArray();
    }

    /**
     * 删除发票抬头
     */
    public function deleteHeader(int $userId, int $id): bool
    {
        $header = $this->repository->findOrFail($id);
        if ((int) $header->user_id !== $userId) {
            throw new BusinessException('无权操作该发票抬头');
        }
        $header->deleted_at = time();
        return (bool) $header->save();
    }

    /**
     * 申请开票
     *
     * @return array<string, mixed>
     */
    public function apply(int $userId, array $data, int $appId = 0): array
    {
        $orderId = (int) ($data['order_id'] ?? 0);
        if (!$orderId) {
            throw new BusinessException('请选择要开票的订单');
        }

        $headerId = (int) ($data['invoice_header_id'] ?? 0);
        $header = null;
        if ($headerId > 0) {
            $header = $this->repository->find($headerId);
            if (!$header || (int) $header->user_id !== $userId) {
                throw new BusinessException('发票抬头不存在');
            }
        } else {
            $header = $this->repository->getDefaultHeader($userId, $appId);
            if (!$header) {
                throw new BusinessException('请先添加发票抬头');
            }
        }

        if ($this->repository->hasRecordForOrder($orderId, $userId)) {
            throw new BusinessException('该订单已申请过开票');
        }

        $orderService = new OrderService();
        $order = $orderService->getOrderDetail($orderId);
        if (!$order || (int) ($order['user_id'] ?? 0) !== $userId) {
            throw new BusinessException('订单不存在或无权操作');
        }

        // 只允许已完成的订单申请开票
        $status = (int) ($order['status'] ?? 0);
        if ($status !== \app\model\Order::ORDER_STATUS_COMPLETE) {
            throw new BusinessException('订单完成后才能申请开票');
        }

        $amount = round((float) ($order['pay_price'] ?? $order['total_price'] ?? 0), 2);
        if ($amount <= 0) {
            throw new BusinessException('订单金额异常，无法开票');
        }

        $record = $this->repository->createRecord([
            'order_id'          => $orderId,
            'invoice_header_id' => (int) $header->id,
            'invoice_content'   => trim((string) ($data['invoice_content'] ?? '')) ?: '商品明细',
            'invoice_amount'    => $amount,
            'invoice_no'        => '',
            'status'            => InvoiceRecord::STATUS_PENDING,
            'app_id'            => $appId,
        ]);

        return (array) $record->toArray();
    }

    /**
     * 开票记录列表
     */
    public function getRecordList(int $userId, int $page = 1, int $pageSize = 20, int $status = 0): array
    {
        return $this->repository->getUserRecords($userId, $page, $pageSize, $status);
    }
}
