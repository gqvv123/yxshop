<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\ApplyService;
use app\service\UserService;
use app\model\Apply;
use app\model\BaseModel;

/**
 * 企业认证控制器
 *
 * GET  /api/v1/verify/info    — 获取当前用户的认证状态
 * POST /api/v1/verify/submit  — 提交企业认证资料
 */
class VerifyController extends BaseController
{
    /** 申请类型：企业认证 */
    const TYPE_ENTERPRISE_VERIFY = 10;

    protected $applyService;
    protected $userService;

    public function __construct()
    {
        $this->applyService = new ApplyService();
        $this->userService = new UserService();
    }

    /**
     * 获取当前用户的认证状态
     */
    public function info(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        // 查最新一条企业认证申请
        $apply = $this->applyService->getLatestByUserAndType($userId, self::TYPE_ENTERPRISE_VERIFY);

        $status     = 0;   // 0=未申请 1=待审核 2=已通过 3=已拒绝
        $statusText = '未认证';
        $detail     = null;

        if ($apply) {
            $map = [
                Apply::STATUS_PENDING   => [1, '审核中'],
                Apply::STATUS_APPROVED  => [2, '已认证'],
                Apply::STATUS_REJECTED  => [3, '已拒绝'],
                Apply::STATUS_PROCESSING => [1, '审核中'],
                Apply::STATUS_COMPLETED => [2, '已认证'],
                Apply::STATUS_CANCELLED => [0, '已取消'],
            ];
            $pair            = $map[$apply->apply_status] ?? [1, '审核中'];
            $status          = $pair[0];
            $statusText      = $pair[1];

            $data = $apply->apply_data ?? [];
            $detail = [
                'id'             => (string) $apply->id,
                'company_name'   => $data['company_name']   ?? '',
                'credit_code'    => $data['credit_code']    ?? '',
                'legal_person'   => $data['legal_person']   ?? '',
                'contact_name'   => $data['contact_name']   ?? '',
                'contact_phone'  => $data['contact_phone']  ?? '',
                'license_image'  => isset($data['license_image']) ? BaseModel::resolveAssetUrl($data['license_image']) : '',
                'id_card_front'  => isset($data['id_card_front']) ? BaseModel::resolveAssetUrl($data['id_card_front']) : '',
                'id_card_back'   => isset($data['id_card_back'])  ? BaseModel::resolveAssetUrl($data['id_card_back'])  : '',
                'reject_reason'  => $apply->audit_remark ?: '',
                'created_at'     => (int) $apply->apply_time > 0
                                    ? date('Y-m-d', (int) $apply->apply_time)
                                    : '',
            ];
        }

        return $this->success([
            'status'      => $status,
            'status_text' => $statusText,
            'detail'      => $detail,
        ]);
    }

    /**
     * 提交企业认证资料
     */
    public function submit(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $companyName  = trim((string) $request->post('company_name', ''));
        $creditCode   = trim((string) $request->post('credit_code', ''));
        $legalPerson  = trim((string) $request->post('legal_person', ''));
        $contactName  = trim((string) $request->post('contact_name', ''));
        $contactPhone = trim((string) $request->post('contact_phone', ''));
        $licenseImage = trim((string) $request->post('license_image', ''));
        $idCardFront  = trim((string) $request->post('id_card_front', ''));
        $idCardBack   = trim((string) $request->post('id_card_back', ''));

        if ($companyName === '') {
            return $this->error('企业名称不能为空');
        }
        if ($creditCode === '') {
            return $this->error('统一社会信用代码不能为空');
        }
        if ($legalPerson === '') {
            return $this->error('法定代表人不能为空');
        }
        if ($contactPhone === '') {
            return $this->error('联系电话不能为空');
        }
        if ($licenseImage === '') {
            return $this->error('请上传营业执照');
        }

        // 检查是否已有待审核的申请
        if ($this->applyService->hasPendingByUserAndType($userId, self::TYPE_ENTERPRISE_VERIFY)) {
            return $this->error('您已有审核中的认证申请，请耐心等待');
        }

        $user = $this->userService->findOrFail($userId);
        $appId = (int) ($user->app_id ?? 0);

        try {
            $apply = $this->applyService->createApply(
                $userId,
                self::TYPE_ENTERPRISE_VERIFY,
                '企业认证 - ' . $companyName,
                $companyName,
                [
                    'company_name'   => $companyName,
                    'credit_code'    => $creditCode,
                    'legal_person'   => $legalPerson,
                    'contact_name'   => $contactName ?: $legalPerson,
                    'contact_phone'  => $contactPhone,
                    'license_image'  => $licenseImage,
                    'id_card_front'  => $idCardFront,
                    'id_card_back'   => $idCardBack,
                ],
                $appId
            );

            return $this->success(
                ['id' => (string) $apply->id],
                '提交成功，我们会在1-3个工作日内审核'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
