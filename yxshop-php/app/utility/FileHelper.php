<?php

namespace app\utility;

class FileHelper
{
    public static function storeFile($file, string $location, ?string $size = null, ?string $removeFile = null): string
    {
        if (!file_exists($location)) {
            mkdir($location, 0755, true);
        }
        if ($removeFile) {
            static::removeFile($location, $removeFile);
        }
        $filename = uniqid() . time() . '.' . $file->getClientOriginalExtension();
        $image = \Intervention\Image\Facades\Image::make(file_get_contents($file));
        if ($size) {
            $sizeArr = explode('x', strtolower($size));
            $image->resize((int)$sizeArr[0], (int)$sizeArr[1]);
        }
        $image->save($location . '/' . $filename);
        return $filename;
    }

    public static function uploadNewFile($file, string $location, ?string $old = null): string
    {
        if (!file_exists($location)) {
            mkdir($location, 0755, true);
        }
        if (!$location) {
            throw new \Exception('File could not been created.');
        }
        if ($old && file_exists($location . '/' . $old) && is_file($location . '/' . $old)) {
            @unlink($location . '/' . $old);
        }
        $filename = uniqid() . time() . '.' . $file->getClientOriginalExtension();
        $file->move($location, $filename);
        return $filename;
    }

    public static function removeFile(string $location, string $removeFile): void
    {
        if (file_exists($location . '/' . $removeFile) && is_file($location . '/' . $removeFile)) {
            @unlink($location . '/' . $removeFile);
        }
    }

    public static function showImage(string $image, ?string $size = null): string
    {
        $file = asset('assets/images/default.jpg');
        if (file_exists($image) && is_file($image)) {
            $file = asset($image);
        } elseif ($size) {
            $file = route('default.image', $size);
        }
        return $file;
    }

    public static function fileFormat(): array
    {
        return [
            'image' => ['jpg', 'png', 'jpeg', 'gif', 'bmp', 'svg', 'webp'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'],
            'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm'],
            'audio' => ['mp3', 'wav', 'ogg', 'flac', 'aac'],
            'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
        ];
    }

    public static function filePath(): array
    {
        return [
            'profile' => [
                'admin' => ['path' => 'assets/images/backend/profile', 'size' => '150x150'],
                'user' => ['path' => 'assets/images/frontend/profile', 'size' => '150x150'],
                'seller' => ['path' => 'assets/images/backend/seller/profile', 'size' => '150x150'],
                'delivery_man' => ['path' => 'assets/images/backend/delivery_man/profile', 'size' => '150x150'],
            ],
            'product' => [
                'featured' => ['path' => 'assets/images/backend/product/featured', 'size' => '800x650'],
                'gallery' => ['path' => 'assets/images/backend/product/gallery', 'size' => '400x400'],
                'thumbnail' => ['path' => 'assets/images/backend/product/thumbnail', 'size' => '300x300'],
            ],
            'category' => [
                'icon' => ['path' => 'assets/images/backend/category/icon', 'size' => '100x100'],
                'banner' => ['path' => 'assets/images/backend/category/banner', 'size' => '800x200'],
            ],
            'banner' => ['path' => 'assets/images/frontend/banner', 'size' => '1200x400'],
            'brand' => ['path' => 'assets/images/backend/brand', 'size' => '200x200'],
            'coupon' => ['path' => 'assets/images/backend/coupon', 'size' => '300x150'],
            'text_editor_file' => ['path' => 'assets/images/text_editor'],
            'notification' => ['path' => 'assets/images/notification', 'size' => '600x300'],
        ];
    }

    public static function exportExcel(string $fileName, array $tileArray = [], array $dataArray = []): void
    {
        $file_name = "order-" . date('Ymdhis', time()) . ".csv";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename=' . $file_name);
        header('Cache-Control: max-age=0');
        $file = fopen('php://output', "a");
        $limit = 1000;
        $calc = 0;
        $tit = [];
        foreach ($tileArray as $v) {
            $tit[] = iconv('UTF-8', 'GB2312//IGNORE', $v);
        }
        fputcsv($file, $tit);
        foreach ($dataArray as $v) {
            $calc++;
            if ($limit == $calc) {
                ob_flush();
                flush();
                $calc = 0;
            }
            $row = [];
            foreach ($v as $t) {
                $row[] = iconv('UTF-8', 'GB2312//IGNORE', $t);
            }
            fputcsv($file, $row);
        }
        fclose($file);
        exit;
    }
}
