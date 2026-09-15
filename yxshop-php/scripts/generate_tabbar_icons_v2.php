<?php
/**
 * 生成 tabbar 图标 base64 编码的 PNG
 * 输出一个 JS 文件，包含 8 个图标的 base64 data URI
 */

$size = 81;
$dir = 'd:/wwwroot/yxshop/yxshop-miniprogram/assets/tabbar/';

function drawHome($img, $color) {
    // 房子：三角屋顶 + 矩形主体 + 门
    $points = [40,12, 72,38, 72,38, 62,38, 62,68, 46,68, 46,50, 34,50, 34,68, 18,68, 18,38, 8,38];
    imagefilledpolygon($img, $points, $color);
}

function drawCategory($img, $color) {
    // 四个圆角方块
    imagefilledrectangle($img, 12, 12, 34, 34, $color);
    imagefilledrectangle($img, 46, 12, 68, 34, $color);
    imagefilledrectangle($img, 12, 46, 34, 68, $color);
    imagefilledrectangle($img, 46, 46, 68, 68, $color);
}

function drawCart($img, $color) {
    // 购物车把手
    imagefilledpolygon($img, [10,14, 20,14, 23,22, 19,22], $color);
    // 车身
    imagefilledpolygon($img, [19,22, 60,22, 56,48, 26,48], $color);
    // 左轮
    imagefilledellipse($img, 28, 58, 12, 12, $color);
    // 右轮
    imagefilledellipse($img, 54, 58, 12, 12, $color);
}

function drawProfile($img, $color) {
    // 头部
    imagefilledellipse($img, 40, 26, 20, 20, $color);
    // 身体（梯形）
    imagefilledpolygon($img, [18,70, 18,58, 28,48, 52,48, 62,58, 62,70], $color);
}

function makeIcon($drawFn, $hexColor) {
    global $size;
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    
    $r = hexdec(substr($hexColor, 1, 2));
    $g = hexdec(substr($hexColor, 3, 2));
    $b = hexdec(substr($hexColor, 5, 2));
    $color = imagecolorallocate($img, $r, $g, $b);
    
    $drawFn($img, $color);
    
    ob_start();
    imagepng($img, null, 6);
    $data = ob_get_clean();
    imagedestroy($img);
    
    return 'data:image/png;base64,' . base64_encode($data);
}

$gray = '#999999';
$orange = '#FF6B35';

$icons = [
    'home' => [
        'normal' => makeIcon('drawHome', $gray),
        'active' => makeIcon('drawHome', $orange),
    ],
    'category' => [
        'normal' => makeIcon('drawCategory', $gray),
        'active' => makeIcon('drawCategory', $orange),
    ],
    'cart' => [
        'normal' => makeIcon('drawCart', $gray),
        'active' => makeIcon('drawCart', $orange),
    ],
    'profile' => [
        'normal' => makeIcon('drawProfile', $gray),
        'active' => makeIcon('drawProfile', $orange),
    ],
];

// 输出 JS 文件
$jsContent = "// 自动生成：tabbar 图标 base64 数据\n";
$jsContent .= "// 普通态: #999999, 选中态: #FF6B35\n";
$jsContent .= "module.exports = " . json_encode($icons, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . ";\n";

$jsPath = $dir . 'icons_data.js';
file_put_contents($jsPath, $jsContent);
echo "Generated: $jsPath (" . strlen($jsContent) . " bytes)\n";

// 同时也保存为单独的 PNG 文件
$icons2 = [
    ['home.png', 'drawHome', $gray],
    ['home-active.png', 'drawHome', $orange],
    ['category.png', 'drawCategory', $gray],
    ['category-active.png', 'drawCategory', $orange],
    ['cart.png', 'drawCart', $gray],
    ['cart-active.png', 'drawCart', $orange],
    ['profile.png', 'drawProfile', $gray],
    ['profile-active.png', 'drawProfile', $orange],
];

foreach ($icons2 as $cfg) {
    list($filename, $drawFn, $color) = $cfg;
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    
    $r = hexdec(substr($color, 1, 2));
    $g = hexdec(substr($color, 3, 2));
    $b = hexdec(substr($color, 5, 2));
    $iconColor = imagecolorallocate($img, $r, $g, $b);
    
    $drawFn($img, $iconColor);
    
    $filepath = $dir . $filename;
    imagepng($img, $filepath, 6);
    imagedestroy($img);
    echo "Generated: $filename (" . filesize($filepath) . " bytes)\n";
}

echo "Done!\n";
