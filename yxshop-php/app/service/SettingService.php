<?php

namespace app\service;

use app\repository\SettingRepository;
use app\model\Setting;
use app\service\SettingEncryptService;
use Exception;

/**
 * 系统设置服务类
 *
 * @property SettingRepository $repository
 */
class SettingService extends BaseService
{
    protected $encryptService;

    public function __construct(SettingRepository $repository = null)
    {
        $repository = $repository ?? new SettingRepository();
        parent::__construct($repository);
        $this->encryptService = new SettingEncryptService();
    }

    /**
     * 获取设置值
     */
    public function getSetting($key, $appId = 0, $default = null)
    {
        try {
            $setting = $this->repository->getByKey($key, $appId);

            if (!$setting) {
                return $default;
            }

            $value = $setting->value;

            if (!empty($setting->is_encrypted) && !empty($value)) {
                $decrypted = $this->encryptService->decrypt($value);
                return $this->encryptService->mask($decrypted);
            }

            switch ($setting->type) {
                case 'json':
                    return json_decode($setting->value, true);
                case 'integer':
                case 'int':
                    return (int) $setting->value;
                case 'float':
                    return (float) $setting->value;
                case 'boolean':
                case 'bool':
                    return (bool) $setting->value;
                default:
                    return $setting->value;
            }

        } catch (Exception $e) {
            $this->logError('获取设置值失败', [
                'key' => $key,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }

    public function getRawSetting($key, $appId = 0, $default = null)
    {
        try {
            $setting = $this->repository->getByKey($key, $appId);

            if (!$setting) {
                return $default;
            }

            $value = $setting->value;

            if (!empty($setting->is_encrypted) && !empty($value)) {
                return $this->encryptService->decrypt($value);
            }

            switch ($setting->type) {
                case 'json':
                    return json_decode($setting->value, true);
                case 'integer':
                case 'int':
                    return (int) $setting->value;
                case 'float':
                    return (float) $setting->value;
                case 'boolean':
                case 'bool':
                    return (bool) $setting->value;
                default:
                    return $setting->value;
            }

        } catch (Exception $e) {
            $this->logError('获取原始设置值失败', [
                'key' => $key,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }

    /**
     * 设置值
     */
    public function setSetting($key, $value, $type = 'string', $group = 'system', $description = '', $appId = 0)
    {
        try {
            $this->logInfo('设置值开始', [
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'app_id' => $appId
            ]);

            $isEncrypted = 0;
            if ($this->encryptService->isEncryptedField($key) && !empty($value)) {
                $value = $this->encryptService->encrypt($value);
                $isEncrypted = 1;
            }

            $setting = $this->repository->getByKey($key, $appId);
            if (!$setting) {
                $setting = new Setting();
                $setting->key = $key;
                $setting->type = $type;
                $setting->group = $group;
                $setting->description = $description;
                $setting->app_id = $appId;
            }

            $setting->is_encrypted = $isEncrypted;

            switch ($type) {
                case 'json':
                    $setting->value = json_encode($value);
                    break;
                default:
                    $setting->value = (string) $value;
                    break;
            }
            $setting->save();

            $this->logInfo('设置值成功', ['key' => $key]);
            return $setting;

        } catch (Exception $e) {
            $this->logError('设置值失败', [
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取分组设置
     */
    public function getGroupSettings($group, $appId = 0)
    {
        try {
            $settings = $this->repository->getByGroup($group, $appId);
            $result = [];

            foreach ($settings as $setting) {
                $value = $setting->value;
                
                // 根据类型解析值
                switch ($setting->type) {
                    case 'json':
                        $value = json_decode($setting->value, true);
                        break;
                    case 'integer':
                    case 'int':
                        $value = (int) $setting->value;
                        break;
                    case 'float':
                        $value = (float) $setting->value;
                        break;
                    case 'boolean':
                    case 'bool':
                        $value = (bool) $setting->value;
                        break;
                }

                $result[$setting->key] = $value;
            }

            return $result;

        } catch (Exception $e) {
            $this->logError('获取分组设置失败', [
                'group' => $group,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取所有设置
     */
    public function getAllSettings($appId = 0)
    {
        try {
            $settings = $this->repository->getAllSettings($appId);
            $result = [];

            foreach ($settings as $key => $setting) {
                $value = $setting->value;
                
                // 根据类型解析值
                switch ($setting->type) {
                    case 'json':
                        $value = json_decode($setting->value, true);
                        break;
                    case 'integer':
                    case 'int':
                        $value = (int) $setting->value;
                        break;
                    case 'float':
                        $value = (float) $setting->value;
                        break;
                    case 'boolean':
                    case 'bool':
                        $value = (bool) $setting->value;
                        break;
                }

                $result[$key] = $value;
            }

            return $result;

        } catch (Exception $e) {
            $this->logError('获取所有设置失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 批量设置
     */
    public function batchSetSettings(array $settings, $appId = 0)
    {
        try {
            $this->logInfo('批量设置开始', ['settings' => $settings, 'app_id' => $appId]);

            foreach ($settings as $key => $value) {
                $this->setSetting($key, $value, 'string', 'system', '', $appId);
            }

            $this->logInfo('批量设置成功', ['app_id' => $appId]);
            return true;

        } catch (Exception $e) {
            $this->logError('批量设置失败', [
                'settings' => $settings,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 删除设置
     */
    public function deleteSetting($key, $appId = 0)
    {
        try {
            $this->logInfo('删除设置开始', ['key' => $key, 'app_id' => $appId]);

            $setting = $this->repository->getByKey($key, $appId);
            if ($setting) {
                $this->repository->delete($setting->id);
            }

            $this->logInfo('删除设置成功', ['key' => $key]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除设置失败', [
                'key' => $key,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取设置统计
     */
    public function getSettingStats($appId = 0)
    {
        try {
            return $this->repository->getSettingStats($appId);

        } catch (Exception $e) {
            $this->logError('获取设置统计失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function findStrategyByIdAndGroup($strategyId, string $group)
    {
        return $this->repository->findByIdAndGroup($strategyId, $group);
    }

    /**
     * 获取某分组下的策略列表
     */
    public function getStrategiesByGroup(string $group): array
    {
        return $this->repository->getByGroupSorted($group);
    }

    /**
     * 更新策略
     *
     * @return bool 是否更新成功
     */
    public function updateStrategy(int $strategyId, string $group, array $data): bool
    {
        $strategy = $this->findStrategyByIdAndGroup($strategyId, $group);
        if (!$strategy) {
            return false;
        }

        $update = [];
        if (array_key_exists('enabled', $data)) {
            $update['value'] = $data['enabled'];
        }
        if (isset($data['config'])) {
            $update['description'] = json_encode($data['config'], JSON_UNESCAPED_UNICODE);
        }

        if (empty($update)) {
            return true;
        }
        return (bool) $this->repository->updateWhere(
            ['id' => $strategyId, 'group' => $group],
            $update
        );
    }
}
