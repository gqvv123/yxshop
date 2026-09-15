<?php

namespace app\service;

use app\exception\BusinessException;

class ComponentSchemaService
{
    public function getSchemas(string $edition = 'community'): array
    {
        $all = config('mini_page_components', []);
        $result = [];

        foreach ($all as $type => $schema) {
            if ($edition === 'community' && $schema['edition'] === 'enterprise') {
                continue;
            }
            $result[$type] = $schema;
        }

        return $result;
    }

    public function getSchemaByType(string $componentType): ?array
    {
        $all = config('mini_page_components', []);
        return $all[$componentType] ?? null;
    }

    public function getDefaultProps(string $componentType): array
    {
        $schema = $this->getSchemaByType($componentType);
        if (!$schema) {
            throw new BusinessException("未知组件类型: {$componentType}");
        }
        return $schema['default_props'];
    }

    public function validateComponentData(string $componentType, array $props): bool
    {
        $schema = $this->getSchemaByType($componentType);
        if (!$schema) {
            throw new BusinessException("未知组件类型: {$componentType}");
        }

        $propsSchema = $schema['props_schema'] ?? [];

        foreach ($propsSchema as $field => $rules) {
            $type = $rules['type'] ?? 'string';

            if ($type === 'hex_color' && isset($props[$field]) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $props[$field])) {
                throw new BusinessException("字段 {$field} 不是有效的HEX颜色值");
            }

            if ($type === 'enum' && isset($props[$field]) && !in_array($props[$field], $rules['values'] ?? [])) {
                throw new BusinessException("字段 {$field} 值不在允许范围内");
            }

            if ($type === 'integer' && isset($props[$field])) {
                if (isset($rules['min']) && $props[$field] < $rules['min']) {
                    throw new BusinessException("字段 {$field} 值不能小于 {$rules['min']}");
                }
                if (isset($rules['max']) && $props[$field] > $rules['max']) {
                    throw new BusinessException("字段 {$field} 值不能大于 {$rules['max']}");
                }
            }

            if ($type === 'array' && isset($props[$field])) {
                if (isset($rules['max_items']) && count($props[$field]) > $rules['max_items']) {
                    throw new BusinessException("字段 {$field} 数量不能超过 {$rules['max_items']}");
                }
            }
        }

        return true;
    }

    public function validatePageDataForPublish(array $pageData): bool
    {
        if (empty($pageData)) {
            throw new BusinessException('页面数据不能为空');
        }

        foreach ($pageData as $index => $component) {
            if (empty($component['component_type'])) {
                throw new BusinessException("第 " . ($index + 1) . " 个组件缺少 component_type");
            }

            $schema = $this->getSchemaByType($component['component_type']);
            if (!$schema) {
                throw new BusinessException("第 " . ($index + 1) . " 个组件类型未知: {$component['component_type']}");
            }

            $publishRules = $schema['validate_rules']['publish'] ?? [];

            $props = $component['props'] ?? [];
            foreach ($publishRules as $field => $rule) {
                $fieldName = str_replace(['.min', '.required', '.max'], '', $field);

                if (str_ends_with($field, '.required') && $rule === true) {
                    if (empty($props[$fieldName]) && $props[$fieldName] !== '0' && $props[$fieldName] !== 0) {
                        throw new BusinessException("组件 {$component['component_type']} 的 {$fieldName} 为必填项");
                    }
                }

                if (str_ends_with($field, '.min') && is_numeric($rule)) {
                    $value = $props[$fieldName] ?? [];
                    if (is_array($value) && count($value) < $rule) {
                        throw new BusinessException("组件 {$component['component_type']} 的 {$fieldName} 至少需要 {$rule} 项");
                    }
                }
            }
        }

        return true;
    }
}