<?php
/**
 * 生成 tabbar 图标 PNG 文件
 * 8 个图标：home, category, cart, profile (普通 + active)
 * 尺寸：81x81px (微信推荐 tabbar 图标尺寸)
 */

$dir = 'd:/wwwroot/yxshop/yxshop-miniprogram/assets/tabbar/';
$size = 81;

// 图标配置：[文件名, SVG路径描述, 颜色]
$icons = [
    // 普通状态（灰色 #999999）
    ['home.png', 'M40,20 L65,42 L60,42 L60,62 L48,62 L48,48 L32,48 L32,62 L20,62 L20,42 L15,42 Z', '#999999'],
    ['category.png', 'M25,25 L38,25 L38,38 L25,38 Z M43,25 L56,25 L56,38 L43,38 Z M25,43 L38,43 L38,56 L25,56 Z M43,43 L56,43 L56,56 L43,56 Z', '#999999'],
    ['cart.png', 'M20,22 L24,22 L28,50 L58,50 L62,30 L30,30 M30,56 Q30,62 36,62 Q42,62 42,56 M52,56 Q52,62 58,62 Q64,62 64,56', '#999999'],
    ['profile.png', 'M40,25 Q28,25 28,37 Q28,45 35,50 L35,58 L45,58 L45,50 Q52,45 52,37 Q52,25 40,25 Z', '#999999'],
    // 选中状态（橙色 #FF6B35）
    ['home-active.png', 'M40,20 L65,42 L60,42 L60,62 L48,62 L48,48 L32,48 L32,62 L20,62 L20,42 L15,42 Z', '#FF6B35'],
    ['category-active.png', 'M25,25 L38,25 L38,38 L25,38 Z M43,25 L56,25 L56,38 L43,38 Z M25,43 L38,43 L38,56 L25,56 Z M43,43 L56,43 L56,56 L43,56 Z', '#FF6B35'],
    ['cart-active.png', 'M20,22 L24,22 L28,50 L58,50 L62,30 L30,30 M30,56 Q30,62 36,62 Q42,62 42,56 M52,56 Q52,62 58,62 Q64,62 64,56', '#FF6B35'],
    ['profile-active.png', 'M40,25 Q28,25 28,37 Q28,45 35,50 L35,58 L45,58 L45,50 Q52,45 52,37 Q52,25 40,25 Z', '#FF6B35'],
];

foreach ($icons as $config) {
    list($filename, $pathData, $color) = $config;
    $filepath = $dir . $filename;
    
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    
    $r = hexdec(substr($color, 1, 2));
    $g = hexdec(substr($color, 3, 2));
    $b = hexdec(substr($color, 5, 2));
    $iconColor = imagecolorallocate($img, $r, $g, $b);
    
    // 使用简单的几何图形绘制图标
    drawIcon($img, $filename, $iconColor, $size);
    
    imagepng($img, $filepath, 9);
    imagedestroy($img);
    
    echo "Generated: $filename (" . filesize($filepath) . " bytes)\n";
}

function drawIcon($img, $filename, $color, $size) {
    $cx = $size / 2;
    
    if (strpos($filename, 'home') !== false) {
        // 房子图标
        $points = [
            $cx, 18,           // 顶点
            68, 38,            // 右上
            60, 38,            // 右内上
            60, 62,            // 右下
            48, 62,            // 右门下
            48, 48,            // 右门上
            32, 48,            // 左门上
            32, 62,            // 左门下
            20, 62,            // 左下
            20, 38,            // 左内上
            12, 38,            // 左上
        ];
        imagefilledpolygon($img, $points, count($points) / 2, $color);
    } elseif (strpos($filename, 'category') !== false) {
        // 四个方块
        imagefilledrectangle($img, 18, 18, 36, 36, $color);
        imagefilledrectangle($img, 44, 18, 62, 36, $color);
        imagefilledrectangle($img, 18, 44, 36, 62, $color);
        imagefilledrectangle($img, 44, 44, 62, 62, $color);
    } elseif (strpos($filename, 'cart') !== false) {
        // 购物车
        // 上方横线（把手）
        imagefilledrectangle($img, 16, 18, 24, 22, $color);
        // 连接线
        imagefilledrectangle($img, 22, 20, 26, 24, $color);
        // 车身（梯形）
        $points = [26, 24, 60, 24, 56, 46, 30, 46];
        imagefilledpolygon($img, $points, 4, $color);
        // 左轮
        imagefilledellipse($img, 32, 54, 10, 10, $color);
        // 右轮
        imagefilledellipse($img, 54, 54, 10, 10, $color);
    } elseif (strpos($filename, 'profile') !== false) {
        // 人像图标
        // 头部
        imagefilledellipse($img, $cx, 28, 18, 18, $color);
        // 身体
        $points = [22, 62, 22, 55, 30, 48, 50, 48, 58, 55, 58, 62];
        imagefilledpolygon($img, $points, 6, $color);
    }
}

echo "Done!\n";
