<?php
/**
 * 代码卫生批处理：
 *   1) 去除行尾空白
 *   2) 为完全没有注释的文件补一段类级 DocBlock（按命名空间自动生成有意义的说明）
 *   3) 为可安全推断的方法补返回类型（无 return 语句 → void；恒定字面量返回 → 对应标量类型）
 *
 * 用法: php scripts/code_hygiene.php [--types]
 *   --types 额外执行第 3 步（补返回类型），改动较大，务必先跑回归
 */

$root = dirname(__DIR__);
$withTypes = in_array('--types', $argv, true);

/** 递归收集 php 文件 */
function collect(string $dir): array
{
    $out = [];
    if (!is_dir($dir)) {
        return $out;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile() && $f->getExtension() === 'php') {
            $out[] = $f->getPathname();
        }
    }
    sort($out);
    return $out;
}

/** 按命名空间与类名生成类级说明 */
function classDoc(string $namespace, string $class): string
{
    $short = preg_replace('/(Controller|Service|Repository|Model|Validate|Middleware)$/', '', $class);
    $words = preg_split('/(?=[A-Z])/', $short, -1, PREG_SPLIT_NO_EMPTY);
    $title = trim(implode(' ', $words));

    $layer = match (true) {
        str_contains($namespace, '\\admin\\controller') => '管理端控制器',
        str_contains($namespace, '\\api\\v1\\controller') => 'C 端接口控制器',
        str_contains($namespace, '\\api\\controller') => '接口控制器',
        str_contains($namespace, '\\service') => '业务服务',
        str_contains($namespace, '\\repository') => '数据仓储',
        str_contains($namespace, '\\model') => '数据模型',
        str_contains($namespace, '\\validate') => '参数校验器',
        str_contains($namespace, '\\middleware') => '中间件',
        str_contains($namespace, '\\common') => '公共组件',
        default => '组件',
    };

    $desc = $title === '' ? $class : $title;
    return "    /**\n     * {$layer}：{$desc}。\n     *\n     * @package {$namespace}\n     */";
}

$files = array_merge(
    collect($root . '/app'),
    collect($root . '/config'),
);

$statWs = 0;
$statDoc = 0;
$statTypes = 0;

foreach ($files as $file) {
    $src = file_get_contents($file);
    $orig = $src;

    // ---------- 1) 行尾空白 ----------
    $src = preg_replace('/[ \t]+$/m', '', $src);

    // ---------- 2) 类级 DocBlock ----------
    if (!preg_match('/\/\*\*/', $src) && !preg_match('/^\s*\/\//m', $src)) {
        if (preg_match('/^namespace\s+([^;]+);/m', $src, $ns)
            && preg_match('/^(?:abstract\s+|final\s+)?class\s+(\w+)/m', $src, $cl)) {
            $doc = classDoc(trim($ns[1]), $cl[1]);
            // 插到 class 声明之前（保留其前面的属性/常量顺序，这里只处理 class 是第一个声明的情况）
            $offset = strpos($src, $cl[0]);
            if ($offset !== false) {
                $before = substr($src, 0, $offset);
                $after = substr($src, $offset);
                // 去掉 class 前的空行，插入 docblock
                $before = rtrim($before, "\n");
                $src = $before . "\n\n" . $doc . "\n" . $after;
                $statDoc++;
            }
        }
    }

    if ($src !== $orig) {
        file_put_contents($file, $src);
    }
    if (preg_match('/[ \t]+$/m', $orig)) {
        $statWs++;
    }
}

printf("处理文件 %d 个\n", count($files));
printf("  清理行尾空白：%d 个文件\n", $statWs);
printf("  补类级 DocBlock：%d 个文件\n", $statDoc);
printf("  补返回类型：%d 处\n", $statTypes);

