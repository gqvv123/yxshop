<?php

namespace app\admin\controller;

use support\Request;
use app\service\SettingService;
use app\service\LanguageService;
use app\service\SettingEncryptService;
use app\service\WechatPayConfigProvider;
use app\service\WechatPayService;
use app\service\AdminService;
use app\service\AdminApiTokenService;

class SettingController extends BaseController
{
    protected $settingService;

    public function __construct()
    {
        parent::__construct();
        $this->settingService = new SettingService();
    }

    public function show(Request $request)
    {
        $appId = $this->getAppId($request);
        $settings = $this->getBasicSettings($appId);
        return $this->success($settings);
    }

    public function store(Request $request)
    {
        $appId = $this->getAppId($request);
        $data = $request->post();
        $this->saveBasicSettings($data, $appId);
        return $this->success(null, '保存成功');
    }

    public function showPayment(Request $request)
    {
        $appId = $this->getAppId($request);
        $settings = $this->getPaymentSettings($appId);
        return $this->success($settings);
    }

    public function storePayment(Request $request)
    {
        $this->requireSuperAdmin($request);
        $appId = $this->getAppId($request);
        $d = $request->post();
        $this->savePaymentSettings($d, $appId);
        return $this->success(null, '保存成功');
    }

    public function showShipping(Request $request)
    {
        $appId = $this->getAppId($request);
        $settings = $this->getShippingSettings($appId);
        return $this->success($settings);
    }

    public function storeShipping(Request $request)
    {
        $appId = $this->getAppId($request);
        $d = $request->post();
        $this->saveShippingSettings($d, $appId);
        return $this->success(null, '保存成功');
    }

    public function showNotification(Request $request)
    {
        $appId = $this->getAppId($request);
        $settings = $this->getNotificationSettings($appId);
        return $this->success($settings);
    }

    public function storeNotification(Request $request)
    {
        $appId = $this->getAppId($request);
        $d = $request->post();
        $this->saveNotificationSettings($d, $appId);
        return $this->success(null, '保存成功');
    }

    public function showTrade(Request $request)
    {
        $appId = $this->getAppId($request);
        $settings = $this->getTradeSettings($appId);
        return $this->success($settings);
    }

    public function storeTrade(Request $request)
    {
        $appId = $this->getAppId($request);
        $d = $request->post();
        $this->saveTradeSettings($d, $appId);
        return $this->success(null, '保存成功');
    }

    public function uploadCert(Request $request)
    {
        $this->requireSuperAdmin($request);
        $appId = $this->getAppId($request);
        $certFile = $request->file('cert_file');
        $keyFile = $request->file('key_file');
        $platformCertFile = $request->file('platform_cert_file');

        if (!$certFile && !$keyFile && !$platformCertFile) {
            return $this->error('请上传证书文件');
        }

        $certDir = runtime_path() . 'certs' . DIRECTORY_SEPARATOR . $appId;
        if (!is_dir($certDir)) {
            mkdir($certDir, 0755, true);
        }

        $certBaseDir = runtime_path() . 'certs' . DIRECTORY_SEPARATOR;
        $random = bin2hex(random_bytes(4));
        $result = [];

        if ($certFile && $keyFile) {
            $certExt = strtolower($certFile->getUploadExtension() ?: pathinfo($certFile->getUploadName() ?? '', PATHINFO_EXTENSION));
            $keyExt = strtolower($keyFile->getUploadExtension() ?: pathinfo($keyFile->getUploadName() ?? '', PATHINFO_EXTENSION));
            if ($certExt !== 'pem' || $keyExt !== 'pem') {
                return $this->error('仅支持上传PEM格式文件');
            }

            $certContent = $certFile->get_content ? $certFile->get_content() : file_get_contents($certFile->getPathname());
            $keyContent = $keyFile->get_content ? $keyFile->get_content() : file_get_contents($keyFile->getPathname());

            if (stripos($certContent, 'BEGIN CERTIFICATE') === false) {
                return $this->error('证书文件格式不正确，请上传PEM格式文件');
            }
            if (stripos($keyContent, 'BEGIN') === false || stripos($keyContent, 'PRIVATE KEY') === false) {
                return $this->error('私钥文件格式不正确，请上传PEM格式文件');
            }

            if (strlen($certContent) > 102400 || strlen($keyContent) > 102400) {
                return $this->error('证书文件大小不能超过100KB');
            }

            $oldCertPath = $this->settingService->getRawSetting('wxpay_cert_path', $appId, '');
            $oldKeyPath = $this->settingService->getRawSetting('wxpay_key_path', $appId, '');
            if (!empty($oldCertPath) && file_exists($oldCertPath) && str_starts_with(realpath($oldCertPath), realpath($certBaseDir))) {
                @unlink($oldCertPath);
            }
            if (!empty($oldKeyPath) && file_exists($oldKeyPath) && str_starts_with(realpath($oldKeyPath), realpath($certBaseDir))) {
                @unlink($oldKeyPath);
            }

            $certSavePath = $certDir . DIRECTORY_SEPARATOR . 'apiclient_cert_' . $random . '.pem';
            $keySavePath = $certDir . DIRECTORY_SEPARATOR . 'apiclient_key_' . $random . '.pem';

            file_put_contents($certSavePath, $certContent);
            file_put_contents($keySavePath, $keyContent);
            chmod($certSavePath, 0600);
            chmod($keySavePath, 0600);

            $this->settingService->setSetting('wxpay_cert_path', $certSavePath, 'string', 'payment', '微信支付证书路径', $appId);
            $this->settingService->setSetting('wxpay_key_path', $keySavePath, 'string', 'payment', '微信支付私钥路径', $appId);
            $result['cert_uploaded'] = true;
            $result['key_uploaded'] = true;
        }

        if ($platformCertFile) {
            $platformExt = strtolower($platformCertFile->getUploadExtension() ?: pathinfo($platformCertFile->getUploadName() ?? '', PATHINFO_EXTENSION));
            if ($platformExt !== 'pem') {
                return $this->error('微信平台证书仅支持PEM格式');
            }
            $platformContent = $platformCertFile->get_content ? $platformCertFile->get_content() : file_get_contents($platformCertFile->getPathname());
            if (stripos($platformContent, 'BEGIN CERTIFICATE') === false) {
                return $this->error('微信平台证书格式不正确');
            }
            if (strlen($platformContent) > 102400) {
                return $this->error('平台证书文件大小不能超过100KB');
            }
            $oldPlatformPath = $this->settingService->getRawSetting('wxpay_platform_cert_path', $appId, '');
            if (!empty($oldPlatformPath) && file_exists($oldPlatformPath) && str_starts_with(realpath($oldPlatformPath), realpath($certBaseDir))) {
                @unlink($oldPlatformPath);
            }
            $platformSavePath = $certDir . DIRECTORY_SEPARATOR . 'platform_cert_' . $random . '.pem';
            file_put_contents($platformSavePath, $platformContent);
            chmod($platformSavePath, 0600);
            $this->settingService->setSetting('wxpay_platform_cert_path', $platformSavePath, 'string', 'payment', '微信平台证书路径', $appId);
            $result['platform_cert_uploaded'] = true;
        }

        WechatPayConfigProvider::clearCache($appId);

        return $this->success($result, '证书上传成功');
    }

    public function testPayment(Request $request)
    {
        $this->requireSuperAdmin($request);
        $appId = $this->getAppId($request);
        $config = WechatPayConfigProvider::getConfig($appId);

        if (!$config->enable) {
            return $this->error('请先启用微信支付');
        }

        $wechatPayService = new WechatPayService();
        $result = $wechatPayService->testConnection($config);

        if ($result['success']) {
            return $this->success($result, $result['message']);
        }
        return $this->error($result['message']);
    }

    private function saveBasicSettings($data, $appId)
    {
        $fields = ['site_name', 'site_title', 'site_keywords', 'site_description', 'service_phone', 'service_email', 'site_logo', 'site_favicon', 'copyright'];
        foreach ($fields as $field) {
            $this->settingService->setSetting($field, $data[$field] ?? '', 'string', 'basic', $field, $appId);
        }
    }

    private function getBasicSettings($appId)
    {
        $fields = ['site_name', 'site_title', 'site_keywords', 'site_description', 'service_phone', 'service_email', 'site_logo', 'site_favicon', 'copyright'];
        $settings = [];
        foreach ($fields as $field) {
            $settings[$field] = $this->settingService->getSetting($field, $appId, '');
        }
        return $settings;
    }

    private function savePaymentSettings($d, $appId)
    {
        if (!empty($d['wxpay_enable']) && empty($d['wxpay_key'])) {
            $existingKey = $this->settingService->getRawSetting('wxpay_key', $appId, '');
            if (empty($existingKey)) {
                throw new \app\exception\BusinessException('启用微信支付时API密钥不能为空');
            }
        }

        if (!empty($d['wxpay_enable'])) {
            if (empty($d['wxpay_appid'])) {
                throw new \app\exception\BusinessException('启用微信支付时小程序AppID不能为空');
            }
            if (empty($d['wxpay_mchid'])) {
                throw new \app\exception\BusinessException('启用微信支付时商户号不能为空');
            }
            if (!empty($d['wxpay_mchid']) && !preg_match('/^\d{10}$/', $d['wxpay_mchid'])) {
                throw new \app\exception\BusinessException('商户号格式不正确');
            }
            if (!empty($d['wxpay_key']) && strlen($d['wxpay_key']) !== 32) {
                throw new \app\exception\BusinessException('API密钥必须为32位');
            }
            if (!empty($d['wxpay_notify']) && !preg_match('/^https:\/\//', $d['wxpay_notify'])) {
                throw new \app\exception\BusinessException('回调地址必须为HTTPS协议的完整URL');
            }
            if (!empty($d['wxpay_refund_notify']) && !preg_match('/^https:\/\//', $d['wxpay_refund_notify'])) {
                throw new \app\exception\BusinessException('退款回调地址必须为HTTPS协议的完整URL');
            }
        }

        $this->settingService->setSetting('wxpay_enable', (int)($d['wxpay_enable'] ?? 0), 'int', 'payment', '是否开启微信支付', $appId);
        $this->settingService->setSetting('wxpay_appid', $d['wxpay_appid'] ?? '', 'string', 'payment', '小程序AppID', $appId);
        $this->settingService->setSetting('wxpay_mchid', $d['wxpay_mchid'] ?? '', 'string', 'payment', '微信商户号', $appId);

        $wxpayKey = $d['wxpay_key'] ?? '';
        if (!empty($wxpayKey)) {
            $this->settingService->setSetting('wxpay_key', $wxpayKey, 'string', 'payment', '微信API密钥', $appId);
        }

        $this->settingService->setSetting('wxpay_notify', $d['wxpay_notify'] ?? '', 'string', 'payment', '微信回调地址', $appId);
        $this->settingService->setSetting('wxpay_refund_notify', $d['wxpay_refund_notify'] ?? '', 'string', 'payment', '微信退款回调地址', $appId);

        $wxpayV3Key = $d['wxpay_v3_key'] ?? '';
        if (!empty($wxpayV3Key)) {
            $this->settingService->setSetting('wxpay_v3_key', $wxpayV3Key, 'string', 'payment', '微信V3密钥', $appId);
        }
        $this->settingService->setSetting('wxpay_serial_no', $d['wxpay_serial_no'] ?? '', 'string', 'payment', '微信证书序列号', $appId);

        $this->settingService->setSetting('alipay_enable', (int)($d['alipay_enable'] ?? 0), 'int', 'payment', '是否开启支付宝', $appId);
        $this->settingService->setSetting('alipay_appid', $d['alipay_appid'] ?? '', 'string', 'payment', '支付宝AppID', $appId);
        $this->settingService->setSetting('alipay_private_key', $d['alipay_private_key'] ?? '', 'string', 'payment', '支付宝私钥', $appId);
        $this->settingService->setSetting('alipay_public_key', $d['alipay_public_key'] ?? '', 'string', 'payment', '支付宝公钥', $appId);

        WechatPayConfigProvider::clearCache($appId);
    }

    private function getPaymentSettings($appId)
    {
        $fields = ['wxpay_enable', 'wxpay_appid', 'wxpay_mchid', 'wxpay_key', 'wxpay_notify', 'wxpay_refund_notify', 'wxpay_v3_key', 'wxpay_serial_no', 'alipay_enable', 'alipay_appid', 'alipay_private_key', 'alipay_public_key'];
        $settings = [];
        $encryptService = new SettingEncryptService();
        $sensitiveFields = ['wxpay_key', 'wxpay_v3_key', 'alipay_private_key', 'alipay_public_key'];
        foreach ($fields as $field) {
            $value = $this->settingService->getSetting($field, $appId, '');
            if (in_array($field, $sensitiveFields) && !empty($value)) {
                $value = $encryptService->mask($value);
            }
            $settings[$field] = $value;
        }

        $certPath = $this->settingService->getRawSetting('wxpay_cert_path', $appId, '');
        $keyPath = $this->settingService->getRawSetting('wxpay_key_path', $appId, '');
        $platformCertPath = $this->settingService->getRawSetting('wxpay_platform_cert_path', $appId, '');
        $settings['wxpay_cert_uploaded'] = !empty($certPath) && file_exists($certPath);
        $settings['wxpay_key_uploaded'] = !empty($keyPath) && file_exists($keyPath);
        $settings['wxpay_platform_cert_uploaded'] = !empty($platformCertPath) && file_exists($platformCertPath);

        return $settings;
    }

    private function saveShippingSettings($d, $appId)
    {
        $this->settingService->setSetting('shipping_template_default', $d['shipping_template_default'] ?? 'flat', 'string', 'shipping', '默认运费模板', $appId);
        $this->settingService->setSetting('shipping_flat_fee', $d['shipping_flat_fee'] ?? '0.00', 'string', 'shipping', '统一运费', $appId);
        $this->settingService->setSetting('shipping_free_limit', $d['shipping_free_limit'] ?? '0.00', 'string', 'shipping', '满额包邮', $appId);
        $enabled = $d['express_enabled'] ?? [];
        $this->settingService->setSetting('express_enabled', $enabled, 'json', 'shipping', '启用物流公司', $appId);
    }

    private function getShippingSettings($appId)
    {
        $fields = ['shipping_template_default', 'shipping_flat_fee', 'shipping_free_limit', 'express_enabled'];
        $settings = [];
        foreach ($fields as $field) {
            $settings[$field] = $this->settingService->getSetting($field, $appId, '');
        }
        return $settings;
    }

    private function saveNotificationSettings($d, $appId)
    {
        $this->settingService->setSetting('notify_inbox_enable', (int)($d['notify_inbox_enable'] ?? 1), 'int', 'notification', '开启站内消息', $appId);
        $this->settingService->setSetting('notify_email_enable', (int)($d['notify_email_enable'] ?? 0), 'int', 'notification', '开启邮件', $appId);
        $this->settingService->setSetting('smtp_host', $d['smtp_host'] ?? '', 'string', 'notification', 'SMTP服务器', $appId);
        $this->settingService->setSetting('smtp_port', (int)($d['smtp_port'] ?? 465), 'int', 'notification', 'SMTP端口', $appId);
        $this->settingService->setSetting('smtp_user', $d['smtp_user'] ?? '', 'string', 'notification', 'SMTP用户', $appId);
        $this->settingService->setSetting('smtp_pass', $d['smtp_pass'] ?? '', 'string', 'notification', 'SMTP密码', $appId);
        $this->settingService->setSetting('notify_sms_enable', (int)($d['notify_sms_enable'] ?? 0), 'int', 'notification', '开启短信', $appId);
        $this->settingService->setSetting('sms_vendor', $d['sms_vendor'] ?? 'aliyun', 'string', 'notification', '短信平台', $appId);
        $this->settingService->setSetting('sms_key', $d['sms_key'] ?? '', 'string', 'notification', '短信Key', $appId);
        $this->settingService->setSetting('sms_secret', $d['sms_secret'] ?? '', 'string', 'notification', '短信Secret', $appId);
        $this->settingService->setSetting('sms_sign', $d['sms_sign'] ?? '【YXShop】', 'string', 'notification', '短信签名', $appId);
    }

    private function getNotificationSettings($appId)
    {
        $fields = ['notify_inbox_enable', 'notify_email_enable', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'notify_sms_enable', 'sms_vendor', 'sms_key', 'sms_secret', 'sms_sign'];
        $settings = [];
        $encryptService = new SettingEncryptService();
        $sensitiveFields = ['smtp_pass', 'sms_key', 'sms_secret'];
        foreach ($fields as $field) {
            $value = $this->settingService->getSetting($field, $appId, '');
            if (in_array($field, $sensitiveFields) && !empty($value)) {
                $value = $encryptService->mask($value);
            }
            $settings[$field] = $value;
        }
        return $settings;
    }

    private function saveTradeSettings($d, $appId)
    {
        $this->settingService->setSetting('order_auto_cancel_minutes', (int)($d['order_auto_cancel_minutes'] ?? 30), 'int', 'trade', '订单自动取消时间(分钟)', $appId);
        $this->settingService->setSetting('auto_confirm_days', (int)($d['auto_confirm_days'] ?? 7), 'int', 'trade', '自动确认收货天数', $appId);
        $this->settingService->setSetting('auto_review_days', (int)($d['auto_review_days'] ?? 15), 'int', 'trade', '自动审核天数', $appId);
        $this->settingService->setSetting('return_apply_days', (int)($d['return_apply_days'] ?? 7), 'int', 'trade', '退货申请天数', $appId);
        $this->settingService->setSetting('invoice_enable', (int)($d['invoice_enable'] ?? 0), 'int', 'trade', '是否开启发票', $appId);
        $this->settingService->setSetting('order_amount_decimals', (int)($d['order_amount_decimals'] ?? 2), 'int', 'trade', '订单金额小数位数', $appId);
    }

    private function getTradeSettings($appId)
    {
        $fields = ['order_auto_cancel_minutes', 'auto_confirm_days', 'auto_review_days', 'return_apply_days', 'invoice_enable', 'order_amount_decimals'];
        $settings = [];
        foreach ($fields as $field) {
            $settings[$field] = $this->settingService->getSetting($field, $appId, '');
        }
        return $settings;
    }

    public function languages(Request $request)
    {
        $languages = $this->languageService->getActive();
        return $this->success($languages);
    }

    public function languageStore(Request $request)
    {
        $data = $request->post();

        if (empty($data['name']) || empty($data['code'])) {
            return $this->error('语言名称和编码不能为空');
        }

        $result = $this->languageService->create($data);
        if (!$result) {
            return $this->error('添加失败');
        }
        return $this->success($result, '添加成功');
    }

    public function languageUpdate(Request $request, $id)
    {
        if (empty($id) || !is_numeric($id)) {
            return $this->error('无效的语言ID');
        }

        $data = $request->post();
        $result = $this->languageService->update($id, $data);
        if (!$result) {
            return $this->error('更新失败');
        }
        return $this->success($result, '更新成功');
    }

    public function languageDestroy(Request $request, $id)
    {
        if (empty($id) || !is_numeric($id)) {
            return $this->error('无效的语言ID');
        }

        $result = $this->languageService->delete($id);
        if (!$result) {
            return $this->error('删除失败');
        }
        return $this->success(null, '删除成功');
    }

    public function languageUpdateStatus(Request $request, $id)
    {
        if (empty($id) || !is_numeric($id)) {
            return $this->error('无效的语言ID');
        }

        $data = $request->post();
        if (!isset($data['status'])) {
            return $this->error('状态值不能为空');
        }

        $result = $this->languageService->update($id, ['status' => (int) $data['status']]);
        if (!$result) {
            return $this->error('操作失败');
        }
        return $this->success(null, '操作成功');
    }

    public function teamIndex(Request $request)
    {
        $appId = $this->getAppId($request);
        $list = $this->adminService->getTeamList($appId);
        return $this->success($list);
    }

    public function teamUpdateRole(Request $request, int $id)
    {
        $currentAdmin = $request->admin;
        if (empty($currentAdmin['is_super_admin'])) {
            return $this->error('无权操作', 403);
        }

        $roleId = $request->input('role_id');
        if (empty($roleId)) {
            return $this->error('角色不能为空');
        }

        try {
            $this->adminService->updateTeamRole($id, $roleId);
            return $this->success(null, '角色更新成功');
        } catch (\app\exception\BusinessException $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function teamInvite(Request $request)
    {
        $email = $request->input('email');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('邮箱格式不正确');
        }

        $appId = $this->getAppId($request);
        $roleId = $request->input('role_id', 0);

        try {
            $admin = $this->adminService->inviteTeamMember($email, $appId, $roleId);
            return $this->success([
                'id' => $admin->id,
                'email' => $admin->email,
                'name' => $admin->username,
            ], '邀请成功');
        } catch (\app\exception\BusinessException $e) {
            return $this->error($e->getMessage(), 409);
        }
    }

    public function teamUpdateStatus(Request $request, int $id)
    {
        $currentAdmin = $request->admin;
        if (empty($currentAdmin['is_super_admin'])) {
            return $this->error('无权操作', 403);
        }

        if ($currentAdmin['id'] == $id) {
            return $this->error('不能操作自己的状态', 422);
        }

        $status = $request->input('status', '');
        if (!in_array($status, ['active', 'disabled', 'pending'], true)) {
            return $this->error('状态值不合法', 422);
        }

        try {
            $admin = $this->adminService->find($id);
            if (!$admin) {
                return $this->error('用户不存在', 404);
            }
            if ($admin->is_super_admin) {
                return $this->error('不能操作超级管理员', 422);
            }
            $this->adminService->updateTeamStatus($id, $status);
            return $this->success(null, '状态更新成功');
        } catch (\app\exception\BusinessException $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function teamDestroy(Request $request, int $id)
    {
        $currentAdmin = $request->admin;
        if (empty($currentAdmin['is_super_admin'])) {
            return $this->error('无权操作', 403);
        }

        if ($currentAdmin['id'] == $id) {
            return $this->error('不能移除自己', 422);
        }

        try {
            $admin = $this->adminService->find($id);
            if (!$admin) {
                return $this->error('用户不存在', 404);
            }
            if ($admin->is_super_admin) {
                return $this->error('不能移除超级管理员', 422);
            }
            $this->adminService->deleteTeamMember($id);
            return $this->success(null, '成员已移除');
        } catch (\app\exception\BusinessException $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function apiKeysIndex(Request $request)
    {
        $appId = $this->getAppId($request);
        $adminId = $request->admin['id'] ?? 0;

        $tokenService = new AdminApiTokenService();
        $tokens = $tokenService->getByAdmin($adminId, $appId);

        $list = $tokens->map(function ($token) {
            $masked = $token->token_hash;
            if (strlen($masked) > 12) {
                $masked = substr($masked, 0, 8) . '...' . substr($masked, -4);
            }
            return [
                'id' => $token->id,
                'name' => $token->token_name,
                'value' => $masked,
                'status' => $token->status,
                'created' => $token->created_at ? date('Y-m-d', (int) $token->created_at) : '',
                'last_used_at' => $token->last_used_at ? date('Y-m-d H:i:s', (int) $token->last_used_at) : null,
            ];
        });

        return $this->success($list);
    }

    public function apiKeysStore(Request $request)
    {
        $appId = $this->getAppId($request);
        $adminId = $request->admin['id'] ?? 0;
        $name = $request->input('name', 'API Key');

        $plainToken = 'sk_' . bin2hex(random_bytes(16));

        $tokenService = new AdminApiTokenService();
        $token = $tokenService->create([
            'admin_id' => $adminId,
            'app_id' => $appId,
            'token_name' => $name,
            'token_hash' => hash('sha256', $plainToken),
            'abilities' => ['*'],
            'status' => AdminApiTokenService::STATUS_ENABLED,
        ]);

        return $this->success([
            'id' => $token->id,
            'name' => $token->token_name,
            'token' => $plainToken,
        ], '创建成功');
    }

    public function regionalShow(Request $request)
    {
        $appId = $this->getAppId($request);
        return $this->success([
            'language' => $this->settingService->getSetting('default_language', $appId, 'zh-CN'),
            'currency' => $this->settingService->getSetting('default_currency', $appId, 'CNY'),
            'timezone' => $this->settingService->getSetting('default_timezone', $appId, 'Asia/Shanghai'),
        ]);
    }

    public function regionalUpdate(Request $request)
    {
        $appId = $this->getAppId($request);
        $data = $request->post();

        $this->settingService->setSetting('default_language', $data['language'] ?? 'zh-CN', 'string', 'regional', '默认语言', $appId);
        $this->settingService->setSetting('default_currency', $data['currency'] ?? 'CNY', 'string', 'regional', '默认货币', $appId);
        $this->settingService->setSetting('default_timezone', $data['timezone'] ?? 'Asia/Shanghai', 'string', 'regional', '默认时区', $appId);

        return $this->success(null, '保存成功');
    }

    public function showWechat(Request $request): Response
    {
        $appId = $this->getAppId($request);
        $settings = $this->settingService->getGroupSettings('wechat', $appId);
        return $this->success($settings);
    }

    public function storeWechat(Request $request): Response
    {
        $admin = $request->admin ?? null;
        if (!$admin || !$admin->isSuper()) {
            throw new \app\exception\BusinessException('仅超级管理员可修改微信配置');
        }

        $data = $request->post();
        $appId = $this->getAppId($request);
        $descriptions = [
            'wechat_official_appid' => '前台C端微信AppID',
            'wechat_official_secret' => '前台C端微信AppSecret',
            'wechat_official_callback_url' => '前台C端微信回调地址',
            'wechat_admin_appid' => '后台管理端微信AppID',
            'wechat_admin_secret' => '后台管理端微信AppSecret',
            'wechat_admin_callback_url' => '后台管理端微信回调地址',
        ];

        foreach ($descriptions as $key => $desc) {
            if (!isset($data[$key])) continue;
            $value = $data[$key];
            if (str_contains($key, 'appid') && !empty($value) && !preg_match('/^wx[a-f0-9]{16}$/i', $value)) {
                throw new \app\exception\BusinessException("{$desc}格式不正确，应为wx开头+16位十六进制");
            }
            $type = str_contains($key, 'appid') || str_contains($key, 'callback') ? 'string' : 'string';
            $this->settingService->setSetting($key, $value, $type, 'wechat', $desc, $appId);
        }

        return $this->success(null, '保存成功');
    }

    private function requireSuperAdmin(Request $request): void
    {
        $admin = $this->admin ?? $request->admin ?? [];
        if (empty($admin['is_super_admin'])) {
            throw new \app\exception\BusinessException('仅超级管理员可操作支付配置');
        }
    }
}
