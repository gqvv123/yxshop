<?php

namespace app\service;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use app\exception\BusinessException;
use app\service\UserService;

/**
 * 分销推荐海报生成服务
 *
 * 使用 endroid/qr-code 生成帶二维码的推荐海报。
 *
 * 功能：
 *  - 生成用戶專屬推荐二维码
 *  - 合成海报圖片（背景圖 + 二维码 + 用戶信息）
 *  - 海报緩存（7 天 TTL）
 */
class DistributionPosterService extends BaseService
{
    /**
     * 海报存储目錄（相對於 public/）
     */
    private const POSTER_DIR = 'uploads/posters';

    /**
     * 海报緩存 TTL（秒）— 7 天
     */
    private const POSTER_CACHE_TTL = 604800;

    /**
     * 二维码尺寸
     */
    private const QR_SIZE = 280;

    /**
     * 海报尺寸
     */
    private const POSTER_WIDTH = 750;
    private const POSTER_HEIGHT = 1334;

    /**
     * 生成推荐海报
     *
     * @param int $userId 用戶ID
     * @param string $nickname 用戶暱稱
     * @param string $avatarUrl 用戶頭像URL
     * @param string $bgImageUrl 背景圖URL（可選，使用默認背景）
     * @return array 海报信息
     */
    public function generatePoster(int $userId, string $nickname, string $avatarUrl = '', string $bgImageUrl = ''): array
    {
        // 構建推荐鏈接
        $baseUrl = rtrim(env('MINIAPP_BASE_URL', ''), '/');
        $referralUrl = $baseUrl . '/pages/login/login?referral_code=' . $userId;

        // 生成二维码
        $qrPath = $this->generateQrCode($referralUrl, $userId);

        // 合成海报
        $posterPath = $this->composePoster($qrPath, $nickname, $avatarUrl, $bgImageUrl, $userId);

        // 返回海报信息
        return [
            'poster_url' => $this->getPublicUrl($posterPath),
            'qr_url' => $this->getPublicUrl($qrPath),
            'referral_url' => $referralUrl,
            'referral_code' => (string) $userId,
            'expires_at' => time() + self::POSTER_CACHE_TTL,
        ];
    }

    /**
     * 生成二维码
     *
     * @param string $url 二维码內容URL
     * @param int $userId 用戶ID（用於文件命名）
     * @return string 二维码文件相對路徑
     */
    private function generateQrCode(string $url, int $userId): string
    {
        $filename = sprintf('qr_%d.png', $userId);
        $relativePath = self::POSTER_DIR . '/' . $filename;
        $absolutePath = $this->ensureDirectory($relativePath);

        // 检查緩存
        if (file_exists($absolutePath) && (time() - filemtime($absolutePath) < self::POSTER_CACHE_TTL)) {
            return $relativePath;
        }

        // 使用 endroid/qr-code 生成二维码
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($url)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(self::QR_SIZE)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        $result->saveToFile($absolutePath);

        return $relativePath;
    }

    /**
     * 合成海报圖片
     *
     * @param string $qrPath 二维码路徑
     * @param string $nickname 暱稱
     * @param string $avatarUrl 頭像URL
     * @param string $bgImageUrl 背景圖URL
     * @param int $userId 用戶ID
     * @return string 海报文件相對路徑
     */
    private function composePoster(string $qrPath, string $nickname, string $avatarUrl, string $bgImageUrl, int $userId): string
    {
        $filename = sprintf('poster_%d.png', $userId);
        $relativePath = self::POSTER_DIR . '/' . $filename;
        $absolutePath = $this->ensureDirectory($relativePath);

        // 检查緩存
        if (file_exists($absolutePath) && (time() - filemtime($absolutePath) < self::POSTER_CACHE_TTL)) {
            return $relativePath;
        }

        // 创建畫布
        $poster = imagecreatetruecolor(self::POSTER_WIDTH, self::POSTER_HEIGHT);

        // 分配顏色
        $white = imagecolorallocate($poster, 255, 255, 255);
        $black = imagecolorallocate($poster, 51, 51, 51);
        $gray = imagecolorallocate($poster, 153, 153, 153);
        $themeColor = imagecolorallocate($poster, 64, 158, 255);

        // 填充背景
        imagefill($poster, 0, 0, $white);

        // 嘗試加载背景圖
        if (!empty($bgImageUrl)) {
            $bgImage = $this->loadImageFromUrl($bgImageUrl);
            if ($bgImage !== null) {
                imagecopyresampled($poster, $bgImage, 0, 0, 0, 0, self::POSTER_WIDTH, self::POSTER_HEIGHT, imagesx($bgImage), imagesy($bgImage));
                imagedestroy($bgImage);
            }
        } else {
            // 繪製漸變背景（簡易版）
            for ($i = 0; $i < self::POSTER_HEIGHT; $i++) {
                $r = (int) (240 - ($i / self::POSTER_HEIGHT) * 20);
                $g = (int) (248 - ($i / self::POSTER_HEIGHT) * 10);
                $b = 255;
                $color = imagecolorallocate($poster, $r, $g, $b);
                imageline($poster, 0, $i, self::POSTER_WIDTH, $i, $color);
            }
        }

        // 加载頭像
        $avatarSize = 120;
        $avatarX = 40;
        $avatarY = 60;
        if (!empty($avatarUrl)) {
            $avatarImage = $this->loadImageFromUrl($avatarUrl);
            if ($avatarImage !== null) {
                // 裁剪為圓形
                $avatarImage = $this->cropToCircle($avatarImage, $avatarSize);
                imagecopy($poster, $avatarImage, $avatarX, $avatarY, 0, 0, $avatarSize, $avatarSize);
                imagedestroy($avatarImage);
            }
        }

        // 写入暱稱
        $fontPath = $this->getFontPath();
        $safeNickname = $this->truncateText($nickname, 20);
        imagettftext($poster, 24, 0, $avatarX + $avatarSize + 20, $avatarY + 50, $white, $fontPath, $safeNickname);

        // 写入推荐語
        $slogan = '邀请你一起來購物，享專屬优惠！';
        imagettftext($poster, 18, 0, $avatarX + $avatarSize + 20, $avatarY + 85, $white, $fontPath, $slogan);

        // 加载二维码
        $qrAbsolutePath = public_path() . '/' . $qrPath;
        if (file_exists($qrAbsolutePath)) {
            $qrImage = imagecreatefrompng($qrAbsolutePath);
            if ($qrImage !== false) {
                $qrX = (self::POSTER_WIDTH - self::QR_SIZE) / 2;
                $qrY = self::POSTER_HEIGHT - self::QR_SIZE - 150;
                imagecopy($poster, $qrImage, (int) $qrX, (int) $qrY, 0, 0, self::QR_SIZE, self::QR_SIZE);
                imagedestroy($qrImage);
            }
        }

        // 写入底部提示
        $tipText = '長按识别二维码，立即注册';
        $tipBox = imagettfbbox(20, 0, $fontPath, $tipText);
        $tipWidth = $tipBox[2] - $tipBox[0];
        $tipX = (self::POSTER_WIDTH - $tipWidth) / 2;
        imagettftext($poster, 20, 0, (int) $tipX, self::POSTER_HEIGHT - 60, $white, $fontPath, $tipText);

        // 保存海报
        imagepng($poster, $absolutePath, 6);
        imagedestroy($poster);

        return $relativePath;
    }

    /**
     * 從 URL 加载圖片
     */
    private function loadImageFromUrl(string $url): ?\GdImage
    {
        // 如果是相對路徑，轉為本地路徑
        if (!str_starts_with($url, 'http')) {
            $localPath = public_path() . '/' . ltrim($url, '/');
            if (file_exists($localPath)) {
                return $this->loadImageFromFile($localPath);
            }
            return null;
        }

        // 下載圖片
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($data)) {
            return null;
        }

        // 创建临时文件
        $tempFile = tempnam(sys_get_temp_dir(), 'img_');
        file_put_contents($tempFile, $data);
        $image = $this->loadImageFromFile($tempFile);
        unlink($tempFile);

        return $image;
    }

    /**
     * 從文件加载圖片（支持 JPEG / PNG / GIF）
     */
    private function loadImageFromFile(string $path): ?\GdImage
    {
        $info = @getimagesize($path);
        if ($info === false) {
            return null;
        }

        return match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path) ?: null,
            IMAGETYPE_PNG  => imagecreatefrompng($path) ?: null,
            IMAGETYPE_GIF  => imagecreatefromgif($path) ?: null,
            default        => null,
        };
    }

    /**
     * 裁剪圖片為圓形
     */
    private function cropToCircle(\GdImage $image, int $size): \GdImage
    {
        // 先縮放到目標尺寸
        $resized = imagecreatetruecolor($size, $size);
        imagealphablending($resized, true);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefill($resized, 0, 0, $transparent);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $size, $size, imagesx($image), imagesy($image));

        // 裁剪為圓形
        $mask = imagecreatetruecolor($size, $size);
        $transparent = imagecolorallocate($mask, 0, 0, 0);
        $black = imagecolorallocate($mask, 255, 255, 255);
        imagefill($mask, 0, 0, $transparent);
        imagefilledellipse($mask, $size / 2, $size / 2, $size, $size, $black);

        // 应用遮罩
        imagecopy($resized, $mask, 0, 0, 0, 0, $size, $size);
        imagedestroy($mask);

        // 处理透明度
        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        return $resized;
    }

    /**
     * 確保目錄存在
     */
    private function ensureDirectory(string $relativePath): string
    {
        $absolutePath = public_path() . '/' . $relativePath;
        $dir = dirname($absolutePath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $absolutePath;
    }

    /**
     * 获取字體文件路徑
     */
    private function getFontPath(): string
    {
        // 嘗試使用系统字體
        $fontPaths = [
            public_path() . '/fonts/SourceHanSansSC-Regular.otf',
            public_path() . '/fonts/NotoSansSC-Regular.otf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            'C:/Windows/Fonts/msyh.ttc',
            'C:/Windows/Fonts/simhei.ttf',
        ];

        foreach ($fontPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // 如果沒有找到字體，使用內置字體（將降级為 imagestring）
        return '';
    }

    /**
     * 截斷文本
     */
    private function truncateText(string $text, int $maxLength): string
    {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }
        return mb_substr($text, 0, $maxLength - 3) . '...';
    }

    /**
     * 获取文件的公開 URL
     */
    private function getPublicUrl(string $relativePath): string
    {
        $baseUrl = rtrim(env('ASSET_BASE_URL', ''), '/');
        if (empty($baseUrl)) {
            return '/' . $relativePath;
        }
        return $baseUrl . '/' . $relativePath;
    }

    /**
     * 清除用戶海报緩存
     */
    public function clearPosterCache(int $userId): void
    {
        $files = [
            self::POSTER_DIR . '/qr_' . $userId . '.png',
            self::POSTER_DIR . '/poster_' . $userId . '.png',
        ];

        foreach ($files as $file) {
            $absolutePath = public_path() . '/' . $file;
            if (file_exists($absolutePath)) {
                @unlink($absolutePath);
            }
        }
    }
}
