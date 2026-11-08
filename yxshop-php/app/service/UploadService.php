<?php

namespace app\service;

use support\Log;
use Throwable;

/**
 * C 端上传服务（小程序：头像、评价图、反馈图、资质图）
 *
 * 与后台 UploadFileService 的区别：
 * - 后台面向文件库管理（分组、审核、配额）；
 * - 这里只做「校验 -> 落盘 -> 记一条上传记录」，返回前端可直接使用的 URL。
 *
 * 安全约束：
 * - 扩展名白名单 + 体积上限；
 * - 文件名由服务端随机生成，不使用用户上传的文件名，杜绝路径穿越；
 * - 目录按 类型/年/月/日 分层，避免单目录文件过多。
 */
class UploadService
{
    private const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const FILE_EXT  = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar'];

    private const IMAGE_MAX_SIZE = 10 * 1024 * 1024;   // 10MB
    private const FILE_MAX_SIZE  = 50 * 1024 * 1024;   // 50MB

    private UploadFileService $fileService;

    public function __construct(?UploadFileService $fileService = null)
    {
        $this->fileService = $fileService ?? new UploadFileService();
    }

    /**
     * 上传图片
     *
     * @return array{success:bool,data:array,message:string}
     */
    public function uploadImage($file, int $userId): array
    {
        return $this->store($file, $userId, 'image', self::IMAGE_EXT, self::IMAGE_MAX_SIZE, 'images');
    }

    /**
     * 上传文件（证件、附件等）
     *
     * @return array{success:bool,data:array,message:string}
     */
    public function uploadFile($file, int $userId): array
    {
        return $this->store($file, $userId, 'file', self::FILE_EXT, self::FILE_MAX_SIZE, 'files');
    }

    /**
     * 删除本人上传的文件
     *
     * @return array{success:bool,data:array,message:string}
     */
    public function deleteFile(string $filePath, int $userId): array
    {
        $filePath = ltrim(str_replace('\\', '/', $filePath), '/');

        // 只允许删除 uploads/ 下的文件，防止越权删除其它目录
        if (strpos($filePath, 'uploads/') !== 0 || strpos($filePath, '..') !== false) {
            return ['success' => false, 'data' => [], 'message' => '非法的文件路径'];
        }

        $fullPath = public_path($filePath);
        if (!is_file($fullPath)) {
            return ['success' => false, 'data' => [], 'message' => '文件不存在'];
        }

        // 校验归属：只允许删除自己上传的文件
        $record = \app\model\UploadFile::where('file_path', $filePath)
            ->where('user_id', $userId)
            ->where('deleted_at', 0)
            ->first();
        if (!$record) {
            return ['success' => false, 'data' => [], 'message' => '无权删除该文件'];
        }

        \app\common\SafeHelper::unlink($fullPath);
        $record->deleted_at = time();
        $record->save();

        return ['success' => true, 'data' => [], 'message' => '删除成功'];
    }

    /**
     * 上传限制，供前端预校验
     */
    public function getUploadConfig(): array
    {
        return [
            'success' => true,
            'data'    => [
                'image_ext'      => self::IMAGE_EXT,
                'file_ext'       => self::FILE_EXT,
                'image_max_size' => self::IMAGE_MAX_SIZE,
                'file_max_size'  => self::FILE_MAX_SIZE,
            ],
            'message' => 'ok',
        ];
    }

    private function store($file, int $userId, string $type, array $allowedExt, int $maxSize, string $dir): array
    {
        try {
            if (!$file) {
                return ['success' => false, 'data' => [], 'message' => '请选择要上传的文件'];
            }

            $originalName = (string) $file->getUploadName();
            $ext = strtolower((string) $file->getUploadExtension());
            if ($ext === '') {
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            }
            if (!in_array($ext, $allowedExt, true)) {
                return ['success' => false, 'data' => [], 'message' => '不支持的文件类型'];
            }

            $size = (int) $file->getSize();
            if ($size <= 0) {
                return ['success' => false, 'data' => [], 'message' => '文件内容为空'];
            }
            if ($size > $maxSize) {
                return [
                    'success' => false,
                    'data'    => [],
                    'message' => '文件大小不能超过' . round($maxSize / 1048576) . 'MB',
                ];
            }

            $relative = date('Y/m/d') . '/' . bin2hex(random_bytes(8)) . '.' . $ext;
            $path = 'uploads/' . $dir . '/' . $relative;
            $fullPath = public_path($path);

            $dirPath = dirname($fullPath);
            if (!is_dir($dirPath) && !mkdir($dirPath, 0755, true) && !is_dir($dirPath)) {
                return ['success' => false, 'data' => [], 'message' => '上传目录创建失败'];
            }

            $file->move($fullPath);

            $data = [
                'storage'       => 'local',
                'original_name' => $originalName,
                'file_path'     => $path,
                'file_url'      => '/' . $path,
                'file_name'     => $relative,
                'file_size'     => $size,
                'file_type'     => $type,
                'file_ext'      => $ext,
                'group_id'      => 0,
                'is_user'       => 1,
                'user_id'       => $userId,
                'app_id'        => 0,
            ];

            $id = 0;
            try {
                $record = $this->fileService->createFile($data);
                $id = $record->id ?? 0;
            } catch (Throwable $e) {
                // 记录失败不阻断上传（例如未配置存储配额），但保留日志便于排查
                Log::warning('[Upload] 上传记录写入失败: ' . $e->getMessage());
            }

            return [
                'success' => true,
                'data'    => [
                    'id'       => $id,
                    'url'      => $data['file_url'],
                    'path'     => $data['file_path'],
                    'filename' => $relative,
                    'size'     => $size,
                ],
                'message' => '上传成功',
            ];
        } catch (Throwable $e) {
            // Log::error('[Upload] 上传失败: ' . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => '上传失败，请稍后重试'];
        }
    }
}
