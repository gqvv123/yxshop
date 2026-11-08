<?php

namespace app\model;

use app\uuid\SnowflakeTrait;
use support\Model;

class BaseModel extends Model
{
    use SnowflakeTrait;

    protected $prefix = 'yxshop_';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * 自动时间戳
     */
    public $timestamps = true;

    /**
     * 时间戳字段名
     */
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * 软删除字段
     */
    const DELETED_AT = 'deleted_at';

    /**
     * 是否启用软删除全局作用域。
     *
     * 默认 null = 自动检测表是否含 deleted_at 列（结果缓存到 self::$softDeleteCache）
     * 显式设为 true/false 可跳过检测
     * 项目采用整型 deleted_at（0=未删，时间戳=已删），与 Eloquent SoftDeletes trait
     * （nullable datetime）不兼容，故用自定义全局 scope 实现等价效果。
     */
    protected $softDeleteEnabled = null;

    /** 缓存：表名 => bool (是否含 deleted_at 列)，整个进程生命周期复用 */
    protected static $softDeleteCache = [];

    /** 缓存：表名 => [列名 => bool]，避免每次写入都探测表结构 */
    protected static $columnCache = [];

    /** 缓存：表名 => [列名 => ['nullable' => bool, 'default' => mixed, 'numeric' => bool]] */
    protected static $columnMetaCache = [];

    /**
     * 写入属性时统一处理「空值」：
     * 1. 前端表单里没填的字段常会传 null（例如广告图片未上传时 image=null），
     *    而这些列在库里是 NOT NULL，Eloquent 一旦显式写入 null 就会绕过数据库默认值，
     *    直接报 "Column 'xxx' cannot be null"。这里对 NOT NULL 列回退到列默认值。
     * 2. 数字列收到空串（例如文章编辑页不选分类时 category_id=''），
     *    严格模式下会报 "Incorrect integer value: '' for column 'xxx'"，
     *    这里统一按 0 处理（列默认值本身就是 0）。
     */
    public function setAttribute($key, $value)
    {
        if ($value === null) {
            $fallback = $this->nullFallbackFor($key);
            if ($fallback !== null) {
                $value = $fallback;
            }
        } elseif ($value === '') {
            $meta = $this->columnMeta($this->getTable(), $key);
            if ($meta !== null && $meta['numeric']) {
                $value = 0;
            }
        }
        return parent::setAttribute($key, $value);
    }

    /**
     * 取某列在 NOT NULL 约束下的兜底值；列可空或未知时返回 null（保持原值）。
     */
    protected function nullFallbackFor(string $column)
    {
        $meta = $this->columnMeta($this->getTable(), $column);
        if ($meta === null) {
            return null; // 列信息未知：保持原值，避免误改语义
        }
        if ($meta['nullable']) {
            return null; // 可空列保持 null
        }
        if ($meta['default'] !== null) {
            return $meta['default'];
        }
        // NOT NULL 且没有可用默认值：按字段类型兜底（数字列给 0，文本列给空串）
        return $meta['numeric'] ? 0 : '';
    }
    /**
     * 读取列元数据（是否可空、默认值、是否数字类型）。
     *
     * 注意：这里刻意不使用「手写 information_schema 查询 + $row->column_default」的方式，
     * 因为 PDO/MySQL 对 information_schema 返回的是**大写原始列名**（IS_NULLABLE / COLUMN_DEFAULT），
     * 按小写取属性会触发 PHP warning；而 webman 的错误处理器会把 warning 升级为 ErrorException，
     * 最终导致列被误判为「可空」，null 原样写库 → Column 'xxx' cannot be null。
     * 改用 Illuminate SchemaBuilder 的 getColumns()，返回结果已统一为小写键 + 布尔 nullable。
     *
     * @return array{nullable: bool, default: mixed, numeric: bool}|null
     */
    protected function columnMeta(string $table, string $column): ?array
    {
        $column = strtolower($column);
        if (!isset(self::$columnMetaCache[$table])) {
            self::$columnMetaCache[$table] = [];
        }
        if (array_key_exists($column, self::$columnMetaCache[$table])) {
            $cached = self::$columnMetaCache[$table][$column];
            return $cached === false ? null : $cached;
        }

        $meta = null;
        try {
            $columns = \support\Db::connection()->getSchemaBuilder()->getColumns($table);
            foreach ($columns as $col) {
                $arr = (array) $col;
                $name = strtolower((string) ($arr['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $typeName = strtolower((string) ($arr['type_name'] ?? ''));
                $default = $arr['default'] ?? null;
                // 表达式默认值（例如 DEFAULT ('')）在 information_schema 里会被写成
                // `_utf8mb4\'\'` 这种带字符集前缀的字面量，直接写进库会变成脏数据，
                // 这里先还原成真实字面量（'' → 空串），还原不了再按类型兜底。
                if (is_string($default) && preg_match("/^_[a-z0-9]+(\\\\'|')(.*)\\1$/is", $default, $m)) {
                    $default = str_replace("\\'", "'", $m[2]);
                }
                if ($default === '') {
                    $default = null;
                }
                self::$columnMetaCache[$table][$name] = [
                    'nullable' => (bool) ($arr['nullable'] ?? true),
                    'default' => $default,
                    'numeric' => in_array($typeName, [
                        'int', 'integer', 'bigint', 'smallint', 'tinyint', 'mediumint',
                        'decimal', 'numeric', 'float', 'double', 'real', 'bit', 'year',
                    ], true),
                ];
            }
        } catch (\Throwable $e) {
            // self::$columnMetaCache[$table] = [];
        }

        $cached = self::$columnMetaCache[$table][$column] ?? false;
        return $cached === false ? null : $cached;
    }

    /**
     * 判断某张表是否存在指定列（进程内缓存）。
     * 日志类表只有 created_at 没有 updated_at，若不做判断，
     * Eloquent 会自动写入 updated_at 导致 "Unknown column" 500。
     */
    protected static function tableHasColumn(string $table, string $column): bool
    {
        if (isset(self::$columnCache[$table]) && array_key_exists($column, self::$columnCache[$table])) {
            return self::$columnCache[$table][$column];
        }
        try {
            $has = \support\Db::connection()->getSchemaBuilder()->hasColumn($table, $column);
        } catch (\Throwable $e) {
            // 表不存在 / 连接异常等情况一律视为不存在，避免阻塞业务
            $has = false;
        }
        self::$columnCache[$table][$column] = $has;
        return $has;
    }

    /**
     * 表里没有 updated_at 列时返回 null，Eloquent 会自动跳过该字段写入。
     */
    public function getUpdatedAtColumn()
    {
        return static::tableHasColumn($this->getTable(), self::UPDATED_AT) ? self::UPDATED_AT : null;
    }

    /**
     * 表里没有 created_at 列时返回 null。
     */
    public function getCreatedAtColumn()
    {
        return static::tableHasColumn($this->getTable(), self::CREATED_AT) ? self::CREATED_AT : null;
    }

    /** 缓存 ASSET_URL，避免每次 getenv */
    protected static ?string $assetBaseUrl = null;

    /**
     * 需要自动拼接域名前缀的图片/文件字段。
     * 子模型可覆盖此属性来增减字段。
     * 以后切 OSS/S3 时这些字段会自动使用新域名。
     */
    protected $imageFields = [
        'logo', 'image', 'cover', 'cover_image', 'avatar', 'avatar_url',
        'main_image', 'goods_image', 'item_image', 'thumb', 'thumbnail',
        'icon', 'file_url', 'file_path', 'link_logo', 'url', 'image_id',
        'video_url',
    ];

    /**
     * 注册全局作用域：自动过滤已软删除的记录（deleted_at = 0 表示未删）。
     * 需查已删记录时用 ->withTrashed() 旁路。
     */
    protected static function booted(): void
    {
        static::addGlobalScope('not_deleted', function ($builder) {
            $model = $builder->getModel();
            if (static::shouldApplySoftDelete($model)) {
                $builder->where($model->getTable() . '.deleted_at', 0);
            }
        });
    }

    /**
     * 判断是否应该套用 not_deleted scope。
     * 显式设值优先，否则按表结构自动检测一次并缓存。
     */
    protected static function shouldApplySoftDelete($model): bool
    {
        if ($model->softDeleteEnabled !== null) {
            return (bool) $model->softDeleteEnabled;
        }
        $table = $model->getTable();
        if (array_key_exists($table, self::$softDeleteCache)) {
            return self::$softDeleteCache[$table];
        }
        $has = static::tableHasColumn($table, 'deleted_at');
        self::$softDeleteCache[$table] = $has;
        return $has;
    }

    /**
     * 旁路软删除 scope（查询含已删记录）
     */
    public function scopeWithTrashed($query)
    {
        return $query->withoutGlobalScope('not_deleted');
    }

    /**
     * 仅查已删记录
     */
    public function scopeOnlyTrashed($query)
    {
        return $query->withoutGlobalScope('not_deleted')->where($this->getTable() . '.deleted_at', '>', 0);
    }

    /**
     * 获取表名
     */
    public function getTable()
    {
        if (empty($this->table)) {
            $table = str_replace('\\', '', snake_case(class_basename($this)));
            $this->table = $this->prefix . str_plural($table);
        }
        return $this->table;
    }

    /**
     * 获取主键
     */
    public function getKeyName()
    {
        return $this->primaryKey ?: 'id';
    }

    /**
     * 获取主键值
     */
    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }

    /**
     * 设置主键值
     */
    public function setKey($value)
    {
        return $this->setAttribute($this->getKeyName(), $value);
    }

    /**
     * 获取创建时间
     */
    public function getCreatedAtAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // 如果是字符串，尝试转换为时间戳
        if (is_string($value)) {
            $timestamp = strtotime($value);
            return $timestamp ? date('Y-m-d H:i:s', $timestamp) : $value;
        }

        // 如果是整数时间戳
        return date('Y-m-d H:i:s', (int) $value);
    }

    /**
     * 设置创建时间
     */
    public function setCreatedAtAttribute($value): void
    {
        $this->attributes['created_at'] = is_numeric($value) ? $value : strtotime($value);
    }

    /**
     * 获取更新时间
     */
    public function getUpdatedAtAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // 如果是字符串，尝试转换为时间戳
        if (is_string($value)) {
            $timestamp = strtotime($value);
            return $timestamp ? date('Y-m-d H:i:s', $timestamp) : $value;
        }

        // 如果是整数时间戳
        return date('Y-m-d H:i:s', (int) $value);
    }

    /**
     * 设置更新时间
     */
    public function setUpdatedAtAttribute($value): void
    {
        $this->attributes['updated_at'] = is_numeric($value) ? $value : strtotime($value);
    }

    /**
     * 软删除
     */
    public function softDelete()
    {
        $this->setAttribute('deleted_at', time());
        $this->setAttribute('updated_at', time());
        return $this->save();
    }

    public function restore()
    {
        $this->setAttribute('deleted_at', 0);
        $this->setAttribute('updated_at', time());
        return $this->save();
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('deleted_at', 0);
    }

    /**

     * 查询已启用的记录
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * 查询指定应用
     */
    public function scopeApp($query, $appId)
    {
        return $query->where('app_id', $appId);
    }

    /**
     * 分页查询
     */
    public function scopePaginate($query, $page = 1, $limit = 15)
    {
        $offset = ($page - 1) * $limit;
        return $query->offset($offset)->limit($limit);
    }

    /**
     * 按时间排序
     */
    public function scopeOrderByTime($query, $order = 'desc')
    {
        return $query->orderBy('created_at', $order);
    }

    /**
     * 按ID排序
     */
    public function scopeOrderById($query, $order = 'desc')
    {
        return $query->orderBy('id', $order);
    }

    /**
     * 获取资源 URL 前缀（本地域名 / OSS / S3）。
     * 优先读 env('ASSET_URL')，没有则回退到 env('DOMAIN')。
     * 以后切换 OSS/S3 只需改 .env 中的 ASSET_URL。
     */
    public static function getAssetBaseUrl(): string
    {
        if (self::$assetBaseUrl === null) {
            $url = getenv('ASSET_URL') ?: getenv('DOMAIN') ?: '';
            self::$assetBaseUrl = rtrim($url, '/');
        }
        return self::$assetBaseUrl;
    }

    /**
     * 将相对路径拼接为完整 URL。
     * - 已是 http(s):// 开头 → 原样返回
     * - // 开头（协议相对）→ 补 https:
     * - / 开头（绝对路径）→ 拼 ASSET_URL
     * - 其他（相对路径）→ 拼 ASSET_URL + /
     * - 空值 → 返回空字符串
     */
    public static function resolveAssetUrl(?string $path): string
    {
        if (!$path || !is_string($path)) return '';
        $trimmed = trim($path);
        if ($trimmed === '') return '';
        if (preg_match('#^https?://#i', $trimmed)) return $trimmed;
        if (str_starts_with($trimmed, '//')) return 'https:' . $trimmed;
        $base = self::getAssetBaseUrl();
        if ($base === '') return $trimmed; // 未配置域名时原样返回
        if (str_starts_with($trimmed, '/')) return $base . $trimmed;
        return $base . '/' . $trimmed;
    }

    /**
     * 序列化时：
     * 1. 将 id 及 *_id 字段转为字符串（防 JS 大整数精度丢失）
     * 2. 将 $imageFields 中的字段自动拼接完整 URL
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $imageFields = array_flip($this->imageFields);
        foreach ($array as $key => $value) {
            // id 转字符串
            if ($key === 'id' || str_ends_with($key, '_id')) {
                if ($value !== null && $value !== '') {
                    $array[$key] = (string) $value;
                }
                continue;
            }
            // 图片字段拼接完整 URL（值为 '0' 或空时跳过，'0' 是旧表默认值不是有效路径）
            if (isset($imageFields[$key]) && is_string($value) && $value !== '' && $value !== '0') {
                $array[$key] = self::resolveAssetUrl($value);
            } elseif (isset($imageFields[$key]) && ($value === '0' || $value === '')) {
                $array[$key] = '';
            }
        }
        return $array;
    }
}
