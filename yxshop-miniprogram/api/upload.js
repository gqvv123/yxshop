/**
 * 上传
 * 对应 yxshop-php: /api/v1/upload/*
 */
const { upload } = require('../utils/request');

module.exports = {
  uploadImage: (filePath, formData) => upload('/api/v1/upload/image', filePath, 'file', formData),
  uploadFile: (filePath, formData) => upload('/api/v1/upload/file', filePath, 'file', formData),
};
