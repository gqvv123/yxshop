<?php

namespace app\observer;

use Illuminate\Database\Eloquent\Model;

abstract class BaseObserver
{
    abstract protected function getModelClass(): string;

    /**
     * 检查审计追踪功能是否可用（企业版）
     */
    protected function auditTrailEnabled(): bool
    {
        return class_exists('\\enterprise\\audit_trail\\Model\\DataChangeLog');
    }

    public function created(Model $model): void
    {
        if (!$this->auditTrailEnabled()) {
            return;
        }

        \enterprise\audit_trail\Model\DataChangeLog::record(
            $model->getTable(),
            $model->getKey(),
            \enterprise\audit_trail\Model\DataChangeLog::CHANGE_TYPE_CREATE,
            null,
            $this->filterData($model->getAttributes(), $model->getTable()),
            '',
            '',
            $model->app_id ?? 0
        );
    }

    public function updated(Model $model): void
    {
        if (!$this->auditTrailEnabled()) {
            return;
        }

        $oldData = $model->getOriginal();
        $newData = $model->getAttributes();
        $tableName = $model->getTable();
        $excluded = SensitiveFields::getExcludedFields($tableName);
        $changedFields = [];

        foreach ($newData as $key => $value) {
            if (in_array($key, $excluded)) {
                continue;
            }
            if (!array_key_exists($key, $oldData) || $oldData[$key] != $value) {
                $changedFields[$key] = [
                    'old' => SensitiveFields::maskValue($key, $oldData[$key] ?? null, $tableName),
                    'new' => SensitiveFields::maskValue($key, $value, $tableName),
                ];
            }
        }

        if (empty($changedFields)) {
            return;
        }

        \enterprise\audit_trail\Model\DataChangeLog::record(
            $tableName,
            $model->getKey(),
            \enterprise\audit_trail\Model\DataChangeLog::CHANGE_TYPE_UPDATE,
            $this->filterData($oldData, $tableName),
            $this->filterData($newData, $tableName),
            json_encode($changedFields, JSON_UNESCAPED_UNICODE),
            '',
            $model->app_id ?? 0
        );
    }

    public function deleted(Model $model): void
    {
        if (!$this->auditTrailEnabled()) {
            return;
        }

        \enterprise\audit_trail\Model\DataChangeLog::record(
            $model->getTable(),
            $model->getKey(),
            \enterprise\audit_trail\Model\DataChangeLog::CHANGE_TYPE_DELETE,
            $this->filterData($model->getAttributes(), $model->getTable()),
            null,
            '',
            '',
            $model->app_id ?? 0
        );
    }

    protected function filterData(array $data, string $tableName): array
    {
        $masked = SensitiveFields::getMaskedFields($tableName);
        foreach ($masked as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***';
            }
        }
        return $data;
    }
}
