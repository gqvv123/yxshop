<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace support;

/**
 * Class Request
 * @package support
 */
class Request extends \Webman\Http\Request
{

    /**
     * 获取参数增强方法
     * @param array $params
     * @return array
     */
    public function more(array $params): array
    {
        $p = [];
        foreach ($params as $param) {
            if (!is_array($param)) {
                $p[$param] = $this->input($param);
            } else {
                if (!isset($param[1])) $param[1] = '';
                if (is_array($param[0])) {
                    $name = $param[0][0] . '/' . $param[0][1];
                    $keyName = $param[0][0];
                } else {
                    $name =  $param[0];
                    $keyName = $param[0];
                }
                $p[$keyName] = $this->input($name, $param[1]);
            }
        }
        return $p;
    }

    /**
     * 判断当前路由是否匹配给定的路由名称模式
     * @param string ...$patterns 路由名称模式，支持通配符 *
     * @return bool
     */
    public function routeIs(...$patterns): bool
    {
        // 获取当前路由名称
        $currentRouteName = current_route_name();
        
        if (empty($currentRouteName)) {
            return false;
        }
        
        // 遍历所有模式
        foreach ($patterns as $pattern) {
            // 如果模式完全匹配
            if ($pattern === $currentRouteName) {
                return true;
            }
            
            // 如果模式包含通配符 *
            if (strpos($pattern, '*') !== false) {
                // 将通配符模式转换为正则表达式
                $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
                if (preg_match($regex, $currentRouteName)) {
                    return true;
                }
            }
        }
        
        return false;
    }

}