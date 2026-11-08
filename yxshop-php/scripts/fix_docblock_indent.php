<?php
/**
 * 修正 code_hygiene 插入的类级 DocBlock：
 *   - 缩进对齐到 class 声明所在层级（顶层为 0）
 *   - 类名按驼峰拆词，标题更易读
 * 用法: php scripts/fix_docblock_indent.php
 */

$root = dirname(__DIR__);

function collectPhp(string $dir): array
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

function prettyClass(string $class): string
{
    $words = preg_split('/(?=[A-Z])/', $class, -1, PREG_SPLIT_NO_EMPTY);
    return trim(implode(' ', $words));
}

$fixed = 0;
foreach (collectPhp($root . '/app') as $file) {
    $src = file_get_contents($file);
    $orig = $src;

    // 匹配 hygiene 插入的注释块：缩进 + /** + * <层>：<类名>。
    $src = preg_replace_callback(
        '/(?<indent>^[ \t]*)\/\*\*\n[ \t]*\*\s*([^\n：]*?)：([A-Za-z0-9_]+)。\n[ \t]*\*\n[ \t]*\* @package ([^\n]+)\n[ \t]*\*\/\n(?=class\s)/m',
        function ($m) {
            $indent = $m[1];
            $layer = $m[2];
            $class = $m[3];
            $pkg = $m[4];
            return $indent . "/**\n"
                . $indent . " * {$layer}：{$class}。\n"
                . $indent . " *\n"
                . $indent . " * @package {$pkg}\n"
                . $indent . " */\n";
        },
        $src
    );

    // 类名驼峰拆词
    $src = preg_replace_callback(
        '/^([ \t]*)\/\*\*\n[ \t]*\* ([^\n：]*?)：([A-Za-z0-9]+)Controller。$/m',
        function ($m) {
            $indent = $m[1];
            $layer = $m[2];
            $base = prettyClass($m[3]);
            return $indent . "/**\n" . $indent . " * {$layer}：{$base} Controller。";
        },
        $src
    );
    $src = preg_replace_callback(
        '/^([ \t]*)\/\*\*\n[ \t]*\* ([^\n：]*?)：([A-Za-z0-9]+)Service。$/m',
        function ($m) {
            $indent = $m[1];
            $layer = $m[2];
            $base = prettyClass($m[3]);
            return $indent . "/**\n" . $indent . " * {$layer}：{$base} Service。";
        },
        $src
    );
    $src = preg_replace_callback(
        '/^([ \t]*)\/\*\*\n[ \t]*\* ([^\n：]*?)：([A-Za-z0-9]+)Repository。$/m',
        function ($m) {
            $indent = $m[1];
            $layer = $m[2];
            $base = prettyClass($m[3]);
            return $indent . "/**\n" . $indent . " * {$layer}：{$base} Repository。";
        },
        $src
    );

    if ($src !== $orig) {
        file_put_contents($file, $src);
        $fixed++;
    }
}
echo "修正 {$fixed} 个文件\n";
