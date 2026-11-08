<?php
$file = 'd:/wwwroot/yxshop/yxshop-miniprogram/assets/tabbar/home.png';
$data = file_get_contents($file);
$header = bin2hex(substr($data, 0, 8));
echo "Header: $header\n";
echo "Valid PNG: " . ($header === '89504e470d0a1a0a' ? 'YES' : 'NO') . "\n";

$im = @imagecreatefrompng($file);
if ($im) {
    echo "GD load: OK\n";
    echo "Width: " . imagesx($im) . " Height: " . imagesy($im) . "\n";
    
    // Check if image has any non-transparent pixels
    $hasContent = false;
    for ($x = 0; $x < imagesx($im); $x++) {
        for ($y = 0; $y < imagesy($im); $y++) {
            $rgba = imagecolorat($im, $x, $y);
            $alpha = ($rgba >> 24) & 0xFF;
            if ($alpha < 127) {
                $hasContent = true;
                echo "First non-transparent pixel at ($x, $y): alpha=$alpha\n";
                break 2;
            }
        }
    }
    echo "Has visible content: " . ($hasContent ? 'YES' : 'NO') . "\n";
    imagedestroy($im);
} else {
    echo "GD load: FAIL\n";
}

// Also check home-active.png
$file2 = 'd:/wwwroot/yxshop/yxshop-miniprogram/assets/tabbar/home-active.png';
$im2 = @imagecreatefrompng($file2);
if ($im2) {
    echo "\nhome-active.png:\n";
    echo "Width: " . imagesx($im2) . " Height: " . imagesy($im2) . "\n";
    $hasContent2 = false;
    for ($x = 0; $x < imagesx($im2); $x++) {
        for ($y = 0; $y < imagesy($im2); $y++) {
            $rgba = imagecolorat($im2, $x, $y);
            $alpha = ($rgba >> 24) & 0xFF;
            if ($alpha < 127) {
                $hasContent2 = true;
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                echo "First pixel at ($x, $y): RGBA=($r,$g,$b,$alpha)\n";
                break 2;
            }
        }
    }
    echo "Has visible content: " . ($hasContent2 ? 'YES' : 'NO') . "\n";
    imagedestroy($im2);
}
