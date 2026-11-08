<?php

namespace app\admin\controller;

use app\exception\BusinessException;
use app\service\InvoiceCompatService;
use app\traits\ApiResponseTrait;
use support\Request;
use support\Response;
use Throwable;

/**
 * 开源版兼容层：发票模块基础 CRUD 控制器。
 *
 * 背景：数据库里 yxshop_invoice_headers / yxshop_invoice_records 表齐全，后台 dist 里
 * 也有对应页面，但 commercial_admin.php（开源版不提供）里才有发票路由，页面请求一律
 * 返回 code=404。本控制器按 dist 中逆向出的路径与调用形状补齐基础读写能力。
 *
 * 分层：本控制器只做「协议适配」（解析路径与请求体、调用服务、组装响应），
 * 业务规则在 \app\service\InvoiceCompatService，数据访问在
 * \app\repository\InvoiceCompatRepository。
 *
 * 安全约束：
 * - 表名只能来自 InvoiceCompatService::TABLES 白名单，绝不接收请求参数；
 * - 写入/查询字段按实际表结构过滤，杜绝列名注入；
 * - 值全部经查询构建器参数绑定；
 * - 分页上限 100，异常不向客户端暴露 SQL 细节。
 */
class InvoiceCompatController
{
    use ApiResponseTrait;

    private InvoiceCompatService $service;

    public function __construct(?InvoiceCompatService $service = null)
    {
        $this->service = $service ?? new InvoiceCompatService();
    }

    public function handle(Request $request, string $path): Response
    {
        try {
            return $this->dispatch($request, trim($path, '/'));
        } catch (BusinessException $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 1);
        } catch (Throwable $e) {
            return $this->error('操作失败，请稍后重试', 1);
        }
    }

    private function dispatch(Request $request, string $path): Response
    {
        [$table, $action, $id] = $this->service->resolve($path);
        if ($table === null) {
            return $this->error('接口不存在', 404);
        }

        $decoded = json_decode($request->rawBody(), true);
        $data = is_array($decoded) ? $decoded : [];
        $scalar = is_scalar($decoded) ? $decoded : null;
        $id = $id ?? ($data['id'] ?? $scalar);
        if (is_array($id) || is_object($id)) {
            $id = null;
        }

        $method = strtoupper($request->method());

        if ($action === 'stats') {
            return $this->success($this->service->stats($table));
        }
        if ($action === 'batch-delete') {
            return $this->success($this->service->batchDelete($table, $this->extractIds($data, $decoded)));
        }
        if ($action !== null) {
            return $this->success($this->service->applyAction($table, $action, $id, $data, $scalar));
        }

        return match ($method) {
            'GET'          => $this->success($this->service->paginate($table, $request->get())),
            'POST'         => $this->success($this->service->create($table, $data)),
            'PUT', 'PATCH' => $this->success($this->service->update($table, $id, $data)),
            'DELETE'       => $this->success($this->service->delete($table, $id)),
            default        => $this->error('请求方法不支持', 1),
        };
    }

    /**
     * 从请求体中提取待删除的 ID 列表。
     *
     * @return array<int, mixed>
     */
    private function extractIds(array $data, $decoded): array
    {
        if (!is_array($decoded)) {
            return [$decoded];
        }
        $ids = $data['ids'] ?? ($data['id'] ?? $decoded);
        return is_array($ids) ? $ids : [$ids];
    }
}
