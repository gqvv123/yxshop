<?php

namespace app\admin\controller;

use support\Request;
use app\service\UploadFileService;

class UploadController extends BaseController
{
    protected $uploadFileService;

    public function __construct()
    {
        parent::__construct();
        $this->uploadFileService = new UploadFileService();
    }

    public function image(Request $request)
    {
        try {
            $file = $request->file('file');
            if (!$file) {
                return $this->error('请选择要上传的文件');
            }

            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $extension = strtolower($file->getUploadExtension());
            if (!in_array($extension, $allowedTypes)) {
                return $this->error('不支持的文件类型');
            }

            $maxSize = 10 * 1024 * 1024;
            if ($file->getSize() > $maxSize) {
                return $this->error('文件大小不能超过10MB');
            }

            $filename = date('Y/m/d/') . uniqid() . '.' . $extension;
            $path = 'uploads/images/' . $filename;
            $fileSize = $file->getSize();
            $originalName = $file->getUploadName();
            $file->move(public_path($path));

            $fileData = [
                'storage' => 'local',
                'original_name' => $originalName,
                'file_path' => $path,
                'file_url' => '/' . $path,
                'file_name' => $filename,
                'file_size' => $fileSize,
                'file_type' => 'image',
                'file_ext' => $extension,
                'group_id' => 0,
                'is_user' => 0,
                'deleted_at' => 0,
                'app_id' => $this->getAppId($request),
                'user_id' => $request->input('user_id', 0),
            ];

            $uploadFile = $this->uploadFileService->createFile($fileData);
            return $this->success([
                'id' => $uploadFile->id,
                'url' => $fileData['file_url'],
                'filename' => $filename
            ], '上传成功');

        } catch (\Exception $e) {
            return $this->error('上传失败：' . $e->getMessage());
        }
    }

    public function file(Request $request)
    {
        try {
            $file = $request->file('file');
            if (!$file) {
                return $this->error('请选择要上传的文件');
            }

            $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar'];
            $extension = strtolower($file->getUploadExtension());
            if (!in_array($extension, $allowedTypes)) {
                return $this->error('不支持的文件类型');
            }

            $maxSize = 50 * 1024 * 1024;
            if ($file->getSize() > $maxSize) {
                return $this->error('文件大小不能超过50MB');
            }

            $filename = date('Y/m/d/') . uniqid() . '.' . $extension;
            $path = 'uploads/files/' . $filename;
            $fileSize = $file->getSize();
            $originalName = $file->getUploadName();
            $file->move(public_path($path));

            $fileData = [
                'storage' => 'local',
                'original_name' => $originalName,
                'file_path' => $path,
                'file_url' => '/' . $path,
                'file_name' => $filename,
                'file_size' => $fileSize,
                'file_type' => 'file',
                'file_ext' => $extension,
                'group_id' => 0,
                'is_user' => 0,
                'deleted_at' => 0,
                'app_id' => $this->getAppId($request),
                'user_id' => $request->input('user_id', 0),
            ];

            $uploadFile = $this->uploadFileService->createFile($fileData);
            return $this->success([
                'id' => $uploadFile->id,
                'url' => $fileData['file_url'],
                'filename' => $filename
            ], '上传成功');

        } catch (\Exception $e) {
            return $this->error('上传失败：' . $e->getMessage());
        }
    }
}
