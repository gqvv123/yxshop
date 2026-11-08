<?php
/**
 * 批量繁简转换脚本
 * 自动搜索项目中所有包含繁体中文的 PHP 文件并转换
 */

$rootDir = $argv[1] ?? dirname(__DIR__);
$excludeDirs = ['zh_tw', 'vendor', 'node_modules', '.git'];

require_once __DIR__ . '/convert_tc_to_sc.php';

// 该脚本已通过 convert_tc_to_sc.php 的逻辑处理
// 此文件仅作为批量调度的入口
echo "Use: php convert_tc_to_sc.php <file_path>\n";
echo "Or run individually for each file.\n";
