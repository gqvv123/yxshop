<?php

namespace app\service;

use support\Log;

class SettingEncryptService
{
    protected $encryptedFields = [
        'wxpay_key',
        'alipay_private_key',
        'alipay_public_key',
        'smtp_pass',
        'sms_key',
        'sms_secret',
        'wechat_official_secret',
        'wechat_admin_secret',
        'ai_openai_api_key',
        'ai_anthropic_api_key',
    ];

    public function encrypt($value)
    {
        $key = $this->getKey();
        if (empty($value)) {
            return $value;
        }
        if (empty($key)) {
            Log::warning('SettingEncryptService: 加密密钥未配置，敏感数据将以明文存储', ['field_value_length' => strlen($value)]);
            return $value;
        }
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    public function decrypt($value)
    {
        $key = $this->getKey();
        if (empty($value) || empty($key)) {
            return $value;
        }
        $decoded = base64_decode($value);
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        $iv = substr($decoded, 0, $ivLength);
        $encrypted = substr($decoded, $ivLength);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        return $decrypted === false ? $value : $decrypted;
    }

    public function mask($value)
    {
        if (empty($value)) {
            return '';
        }
        $len = mb_strlen($value);
        if ($len <= 5) {
            return str_repeat('*', $len);
        }
        $start = mb_substr($value, 0, 2);
        $end = mb_substr($value, -3);
        return $start . '****' . $end;
    }

    public function isEncryptedField($key)
    {
        return in_array($key, $this->encryptedFields);
    }

    protected function getKey()
    {
        return getenv('SETTING_ENCRYPT_KEY') ?: '';
    }
}