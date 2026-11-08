<?php
/**
 * 管理后台静态构建产物完整性校验
 *
 * 用途：public/yxadmin 只有 Vite 构建产物、没有前端源码。上游裁剪或升级 dist 后，
 *       常出现「动态 import / __vite__mapDeps 指向不存在的 chunk」，表现为点击菜单白屏。
 *       本脚本扫描所有 JS 中的静态 import、动态 import 和预加载表字面量，核对文件是否齐备，
 *       并校验 *.gz 预压缩文件与源文件是否一致（Nginx gzip_static 会优先返回 .gz）。
 *
 * 用法：php scripts/verify_admin_dist.php
 * 退出码：0 = 完整；1 = 存在缺失或不一致；2 = 目录不存在
 */

$assets = realpath(__DIR__ . '/../public/yxadmin/assets');
if ($assets === false || !is_dir($assets)) {
    fwrite(STDERR, "未找到 public/yxadmin/assets 目录\n");
    exit(2);
}

$files = [];
foreach (scandir($assets) as $f) {
    if (is_file($assets . '/' . $f)) {
        $files[$f] = true;
    }
}

$refs = [];
$jsCount = 0;
foreach (array_keys($files) as $f) {
    if (!preg_match('/\.js$/', $f) || preg_match('/\.bak\./', $f)) {
        continue;
    }
    $jsCount++;
    $code = file_get_contents($assets . '/' . $f);
    // 保留相对路径，避免 basename() 把 "./sub/foo.js" 误判成根目录下的 "foo.js"
    $add = function ($path) use (&$refs, $f) {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#^.*?/assets/#', '', $path);
        $path = preg_replace('#^\./#', '', $path);
        if (preg_match('/\.(js|css)$/', $path)) {
            $refs[$path][$f] = true;
        }
    };
    // 静态 import / export ... from / 动态 import("...")
    if (preg_match_all('/(?:from|import)\s*["\']([^"\']+)["\']/', $code, $m)) {
        foreach ($m[1] as $path) {
            $add($path);
        }
    }
    // __vite__mapDeps 字符串表：形如 "assets/foo-ABC123.js"
    if (preg_match_all('/(?:\.\/|assets\/)([A-Za-z0-9_.-]+\.(?:js|css))/', $code, $m)) {
        foreach ($m[1] as $name) {
            $add($name);
        }
    }
}

$missing = [];
foreach ($refs as $name => $by) {
    if (!isset($files[$name]) && !is_file($assets . '/' . $name)) {
        $missing[$name] = array_keys($by);
    }
}

// 入口 HTML 自身引用的资源也要核对：缺一个就会整站白屏
$indexPath = dirname($assets) . '/index.html';
if (is_file($indexPath)) {
    $html = file_get_contents($indexPath);
    if (preg_match_all('/(?:src|href)="([^"]+\.(?:js|css))"/', $html, $m)) {
        foreach ($m[1] as $path) {
            $name = basename($path);
            if (!isset($files[$name])) {
                $missing[$name] = ['index.html'];
            }
        }
    }
}

$gzIssues = [];
foreach (array_keys($files) as $f) {
    if (!preg_match('/\.gz$/', $f)) {
        continue;
    }
    $src = $assets . '/' . substr($f, 0, -3);
    if (!is_file($src)) {
        $gzIssues[] = "$f 找不到对应的源文件";
        continue;
    }
    $decoded = @gzdecode(file_get_contents($assets . '/' . $f));
    if ($decoded !== file_get_contents($src)) {
        $gzIssues[] = "$f 与源文件不一致（请重新 gzip）";
    }
}

printf("扫描 JS 文件 : %d\n", $jsCount);
printf("实际资源文件 : %d\n", count($files));
printf("被引用文件   : %d\n", count($refs));
printf("缺失引用     : %d\n", count($missing));
foreach ($missing as $name => $by) {
    printf("  - %s  (被 %d 个文件引用，例: %s)\n", $name, count($by), $by[0]);
}
printf("gz 校验      : %d 个文件，%d 处问题\n", count(array_filter(array_keys($files), function ($f) {
    return substr($f, -3) === '.gz';
})), count($gzIssues));
foreach ($gzIssues as $issue) {
    printf("  - %s\n", $issue);
}

if ($missing || $gzIssues) {
    fwrite(STDERR, "\n校验未通过\n");
    exit(1);
}
printf("\n校验通过：模块引用闭合，预压缩文件一致。\n");
exit(0);
