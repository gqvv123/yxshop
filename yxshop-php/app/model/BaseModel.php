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
    protected static function booted()
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
        try {
            $has = \support\Db::connection()->getSchemaBuilder()->hasColumn($table, 'deleted_at');
        } catch (\Throwable $e) {
            // 表不存在 / 连接异常等情况一律视为不启用，避免阻塞业务
            $has = false;
        }
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
    public function setCreatedAtAttribute($value)
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
    public function setUpdatedAtAttribute($value)
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
