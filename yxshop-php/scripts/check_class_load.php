<?php
/**
 * 类加载自检脚本。
 *
 * 用途：批量加载 app/ 下的所有类，捕获「子类方法签名与父类/接口不兼容」
 * 这类 E_COMPILE_ERROR。
 *
 * 背景：PHP 8.5 下，若子类把父类方法的返回类型写成更宽的类型
 * （例如父类 `: array`，子类不写），webman 的 worker 会在启动时
 * 反复报 "must be compatible with" 并崩溃重启，表现为接口随机 500
 * 或连接被重置。这类问题 php -l 检查不出来（语法合法），只有真正
 * 加载类才会暴露。
 *
 * 用法：php scripts/check_class_load.php
 */

require __DIR__ . '/../vendor/autoload.php';

$errors = [];
$checked = 0;

// 这些目录下的文件不是类（语言包等），直接排除
$skipDirs = ['/language/', '/languages/'];

// CLI 命令类依赖 Symfony Console，只在 webman CLI 进程里可用，
// 单独用 HTTP 进程加载会报 "Class ... not found"，属预期行为
$skipClasses = [
    'app\\command\\MigrateCommand',
    'app\\command\\LogCleanupCommand',
    'app\\command\\GenerateModelsCommand',
];

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../app'));
foreach ($rii as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    foreach ($skipDirs as $skip) {
        if (strpos($path, $skip) !== false) {
            continue 2;
        }
    }
    $relative = substr($path, strlen(__DIR__ . '/../app/'), -4);
    $class = 'app\\' . str_replace('/', '\\', $relative);

    // app/functions.php 是函数文件不是类，跳过
    if ($class === 'app\\functions') {
        continue;
    }
    if (in_array($class, $skipClasses, true)) {
        continue;
    }
    if (!preg_match('/^app\\\\[A-Za-z0-9_\\\\]+$/', $class)) {
        continue;
    }

    try {
        if (!class_exists($class) && !interface_exists($class) && !trait_exists($class)) {
            continue;
        }
        // 反射会强制解析父类与接口，签名不兼容在此抛出
        $rc = new ReflectionClass($class);
        $rc->getMethods();
        $checked++;
    } catch (Throwable $e) {
        $errors[] = $class . ' => ' . $e->getMessage();
    }
}

if ($errors) {
    echo '=== 发现 ' . count($errors) . ' 个类加载错误 ===' . PHP_EOL;
    foreach ($errors as $error) {
        echo $error . PHP_EOL;
    }
    exit(1);
}

echo '已检查 ' . $checked . ' 个类，全部加载正常' . PHP_EOL;
exit(0);
