<?php

namespace app\service;

class CaptchaService
{
    /**
     * 生成传统数字字母验证码
     */
    public function generate()
    {
        $code = strtoupper(substr(md5(uniqid()), 0, 4));
        $uuid = \app\uuid\Snowflake::generate();

        $width = 120;
        $height = 40;
        $image = imagecreate($width, $height);
        $bgColor = imagecolorallocate($image, 240, 240, 240);
        $textColor = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $bgColor);
        imagestring($image, 5, 30, 10, $code, $textColor);
        for ($i = 0; $i < 5; $i++) {
            $lineColor = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
            imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
        }
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);

        $base64 = 'data:image/png;base64,' . base64_encode($imageData);

        \support\Redis::setex("captcha:{$uuid}", 300, $code);

        return [
            'uuid' => $uuid,
            'image' => $base64,
        ];
    }

    /**
     * 验证传统数字字母验证码
     */
    public function verify(string $uuid, string $code): bool
    {
        if (empty($uuid) || empty($code)) {
            return false;
        }

        $cachedCode = \support\Redis::get("captcha:{$uuid}");
        \support\Redis::del("captcha:{$uuid}");

        if (!$cachedCode) {
            return false;
        }

        return strtoupper($code) === strtoupper($cachedCode);
    }

    /**
     * 生成拼图滑块验证码
     */
    public function generatePuzzle()
    {
        $uuid = \app\uuid\Snowflake::generate();

        $width = 300;
        $height = 150;
        $blockSize = 40;

        // 创建背景画布
        $bgImage = imagecreatetruecolor($width, $height);

        // 绘制渐变背景
        $startColor = imagecolorallocate($bgImage, rand(180, 230), rand(180, 230), rand(180, 230));
        imagefill($bgImage, 0, 0, $startColor);

        // 绘制随机干扰图形
        for ($i = 0; $i < 30; $i++) {
            $color = imagecolorallocatealpha($bgImage, rand(0, 255), rand(0, 255), rand(0, 255), rand(40, 80));
            imagefilledellipse($bgImage, rand(0, $width), rand(0, $height), rand(10, 40), rand(10, 40), $color);
        }

        // 绘制随机线条
        for ($i = 0; $i < 8; $i++) {
            $color = imagecolorallocatealpha($bgImage, rand(0, 255), rand(0, 255), rand(0, 255), rand(20, 60));
            imageline($bgImage, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $color);
        }

        // 目标位置 (确保在合理范围内)
        $targetX = rand(60, $width - $blockSize - 20);
        $targetY = rand(20, $height - $blockSize - 20);

        // 创建滑块图片（先从背景中截取）
        $sliderImage = imagecreatetruecolor($blockSize, $blockSize);
        imagealphablending($sliderImage, true);
        imagesavealpha($sliderImage, true);
        $transparent = imagecolorallocatealpha($sliderImage, 0, 0, 0, 127);
        imagefill($sliderImage, 0, 0, $transparent);

        // 从背景图截取滑块区域
        imagecopy($sliderImage, $bgImage, 0, 0, $targetX, $targetY, $blockSize, $blockSize);

        // 给滑块添加边框
        $borderColor = imagecolorallocate($sliderImage, 24, 144, 255);
        imagerectangle($sliderImage, 0, 0, $blockSize - 1, $blockSize - 1, $borderColor);

        // 在背景图上挖空缺口
        $shadowColor = imagecolorallocatealpha($bgImage, 0, 0, 0, 60);
        imagefilledrectangle($bgImage, $targetX, $targetY, $targetX + $blockSize - 1, $targetY + $blockSize - 1, $shadowColor);

        // 给缺口加边框提示
        $holeBorderColor = imagecolorallocatealpha($bgImage, 255, 255, 255, 80);
        imagerectangle($bgImage, $targetX, $targetY, $targetX + $blockSize - 1, $targetY + $blockSize - 1, $holeBorderColor);

        // 输出背景图 base64
        ob_start();
        imagepng($bgImage);
        $bgData = ob_get_contents();
        ob_end_clean();
        imagedestroy($bgImage);

        // 输出滑块图 base64
        ob_start();
        imagepng($sliderImage);
        $sliderData = ob_get_contents();
        ob_end_clean();
        imagedestroy($sliderImage);

        $bgBase64 = 'data:image/png;base64,' . base64_encode($bgData);
        $sliderBase64 = 'data:image/png;base64,' . base64_encode($sliderData);

        // 存储到 Redis (targetX 为目标位置)
        \support\Redis::setex("puzzle_captcha:{$uuid}", 300, json_encode([
            'target_x' => $targetX,
            'target_y' => $targetY,
        ]));

        return [
            'uuid' => $uuid,
            'bg_image' => $bgBase64,
            'slider_image' => $sliderBase64,
            'slider_size' => $blockSize,
            'bg_width' => $width,
            'bg_height' => $height,
            'target_y' => $targetY,
        ];
    }

    /**
     * 验证拼图滑块验证码
     */
    public function verifyPuzzle(string $uuid, float $x): bool
    {
        if (empty($uuid) || $x < 0) {
            return false;
        }

        $cached = \support\Redis::get("puzzle_captcha:{$uuid}");
        \support\Redis::del("puzzle_captcha:{$uuid}");

        if (!$cached) {
            return false;
        }

        $data = json_decode($cached, true);
        if (!isset($data['target_x'])) {
            return false;
        }

        $tolerance = 6; // 容差像素
        return abs($x - $data['target_x']) <= $tolerance;
    }
}
