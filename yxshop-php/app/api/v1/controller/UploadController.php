<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\UploadService;

class UploadController extends BaseController
{
    protected $uploadService;
    
    public function __construct()
    {
        $this->uploadService = new UploadService();
    }
    
    /**
     * 上传图片
     */
    public function uploadImage(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        
        if (!$userId) {
            return $this->error('用户未登录');
        }
        
        $file = $request->file('image');
        
        if (!$file) {
            return $this->error('请选择要上传的图片');
        }
        
        $result = $this->uploadService->uploadImage($file, $userId);
        
        if ($result['success']) {
            return $this->success($result['data'], $result['message']);
        } else {
            return $this->error($result['message']);
        }
    }
    
    /**
     * 上传文件
     */
    public function uploadFile(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        
        if (!$userId) {
            return $this->error('用户未登录');
        }
        
        $file = $request->file('file');
        
        if (!$file) {
            return $this->error('请选择要上传的文件');
        }
        
        $result = $this->uploadService->uploadFile($file, $userId);
        
        if ($result['success']) {
            return $this->success($result['data'], $result['message']);
        } else {
            return $this->error($result['message']);
        }
    }
    
    /**
     * 删除文件
     */
    public function deleteFile(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        $filePath = $request->post('file_path');
        
        if (!$userId) {
            return $this->error('用户未登录');
        }
        
        if (!$filePath) {
            return $this->error('文件路径不能为空');
        }
        
        $result = $this->uploadService->deleteFile($filePath, $userId);
        
        if ($result['success']) {
            return $this->success(null, $result['message']);
        } else {
            return $this->error($result['message']);
        }
    }
    
    /**
     * 获取上传配置
     */
    public function getUploadConfig(Request $request)
    {
        $result = $this->uploadService->getUploadConfig();
        
        if ($result['success']) {
            return $this->success($result['data']);
        } else {
            return $this->error($result['message']);
        }
    }
} 