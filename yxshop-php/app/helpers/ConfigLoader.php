<?php

namespace app\helpers;

use Symfony\Component\Yaml\Yaml;
use Webman\Bootstrap;
use Workerman\Worker;

class ConfigLoader implements Bootstrap
{
    private static $config = null;
    private static $configFile = null;

    /**
     * Bootstrap 启动方法
     */
    public static function start(?Worker $worker)
    {
        // 预加载配置
        self::load();
    }

    /**
     * 加载配置文件
     */
    public static function load($configFile = null)
    {
        if ($configFile === null) {
            $configFile = base_path() . '/app.yaml';
        }
        
        if (self::$config === null || self::$configFile !== $configFile) {
            self::$configFile = $configFile;
            
            if (!file_exists($configFile)) {
                throw new \Exception("配置文件不存在: {$configFile}");
            }
            
            $yamlContent = file_get_contents($configFile);
            self::$config = Yaml::parse($yamlContent);
        }
        
        return self::$config;
    }

    /**
     * 获取配置值
     */
    public static function get($key, $default = null)
    {
        $config = self::load();
        
        // 支持点号分隔的嵌套键
        $keys = explode('.', $key);
        $value = $config;
        
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }
        
        return $value;
    }

    /**
     * 检查配置是否存在
     */
    public static function has($key)
    {
        return self::get($key) !== null;
    }

    /**
     * 获取所有配置
     */
    public static function all()
    {
        return self::load();
    }

    /**
     * 重新加载配置
     */
    public static function reload()
    {
        self::$config = null;
        return self::load();
    }
} 