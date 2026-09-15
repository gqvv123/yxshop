<?php
/**
 * @desc app.php 描述信息
 *
 * @author Tinywan(ShaoBo Wan)
 * @date 2022/3/10 19:46
 */

return [
    'enable' => true,
    'storage' => [
        'default' => 'local', // local：本地 oss：阿里云 cos：腾讯云 qos：七牛云
        'single_limit' => 1024 * 1024 * 200, // 单个文件的大小限制，默认200M 1024 * 1024 * 200
        'total_limit' => 1024 * 1024 * 200, // 所有文件的大小限制，默认200M 1024 * 1024 * 200
        'nums' => 10, // 文件数量限制，默认10
        'include' => [], // 被允许的文件类型列表
        'exclude' => [], // 不被允许的文件类型列表
        // 本地对象存储
        'local' => [
            'adapter' => \Tinywan\Storage\Adapter\LocalAdapter::class,
            'root' => runtime_path().'/storage',
            'dirname' => function () {
                return date('Ymd');
            },
            'domain' => env('domain', 'http://127.0.0.1:8777'),
            'uri' => '/runtime', // 如果 domain + uri 不在 public 目录下，请做好软链接，否则生成的url无法访问
            'algo' => 'sha1',
        ],
        // 阿里云对象存储
        'oss' => [
            'adapter' => \Tinywan\Storage\Adapter\OssAdapter::class,
            'accessKeyId' => env('OSS_ACCESS_KEY_ID', ''),
            'accessKeySecret' => env('OSS_ACCESS_KEY_SECRET', ''),
            'bucket' => env('OSS_BUCKET', ''),
            'dirname' => function () {
                return 'storage';
            },
            'domain' => env('OSS_DOMAIN', ''),
            'endpoint' => env('OSS_ENDPOINT', 'oss-cn-hangzhou.aliyuncs.com'),
            'algo' => 'sha1',
        ],
        // 腾讯云对象存储
        'cos' => [
            'adapter' => \Tinywan\Storage\Adapter\CosAdapter::class,
            'secretId' => env('COS_SECRET_ID', ''),
            'secretKey' => env('COS_SECRET_KEY', ''),
            'bucket' => env('COS_BUCKET', ''),
            'dirname' => 'storage',
            'domain' => env('COS_DOMAIN', ''),
            'region' => env('COS_REGION', 'ap-shanghai'),
        ],
        // 七牛云对象存储
        'qiniu' => [
            'adapter' => \Tinywan\Storage\Adapter\QiniuAdapter::class,
            'accessKey' => env('QINIU_ACCESS_KEY', ''),
            'secretKey' => env('QINIU_SECRET_KEY', ''),
            'bucket' => env('QINIU_BUCKET', ''),
            'dirname' => 'storage',
            'domain' => 'http://webman.oss.tinywan.com',
        ],
        // aws
        's3' => [
            'adapter' => \Tinywan\Storage\Adapter\S3Adapter::class,
            'key' => env('S3_KEY', ''),
            'secret' => env('S3_SECRET', ''),
            'bucket' => env('S3_BUCKET', ''),
            'dirname' => 'storage',
            'domain' => 'http://webman.oss.tinywan.com',
            'region' => 'S3_REGION',
            'version' => 'latest',
            'use_path_style_endpoint' => true,
            'endpoint' => 'S3_ENDPOINT',
            'acl' => 'public-read',
        ],
    ],
];
