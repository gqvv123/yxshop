<?php
/**
 * YXShop 代码质量审计器
 * 7 个维度共 100 分：语法 10 / 安全 25 / 架构 15 / 错误处理 15 / 性能 10 / 规范 15 / 可维护性 10
 * 每类问题扣分封顶，避免「一处问题扣满分」的失真。
 * 用法: php scripts/audit_code_quality.php
 */

$root = dirname(__DIR__);
$appDir = $root . '/app';


/**
 * 解析文件中的具名方法，返回其签名信息：
 *   typed     是否已声明返回类型
 *   inferable 是否可安全推断（方法体没有 return 语句 → void）
 */
function methodSignatures(string $src): array
{
    $out = [];
    $tokens = token_get_all($src);
    $n = count($tokens);

    for ($i = 0; $i < $n; $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) {
            continue;
        }
        $j = $i + 1;
        while ($j < $n && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            $j++;
        }
        if ($j >= $n || !is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING) {
            continue;   // 闭包 / 箭头函数
        }
        $name = $tokens[$j][1];
        if (in_array($name, ['__construct', '__destruct', '__clone', '__sleep', '__wakeup', '__serialize', '__unserialize', '__set_state'], true)) {
            continue;   // 魔术方法不允许声明返回类型
        }

        // 参数列表右括号
        $k = $j;
        while ($k < $n && $tokens[$k] !== '(') {
            $k++;
        }
        if ($k >= $n) {
            continue;
        }
        $depth = 0;
        $close = -1;
        for ($m = $k; $m < $n; $m++) {
            $text = is_array($tokens[$m]) ? $tokens[$m][1] : $tokens[$m];
            if ($text === '(') {
                $depth++;
            } elseif ($text === ')') {
                $depth--;
                if ($depth === 0) {
                    $close = $m;
                    break;
                }
            }
        }
        if ($close < 0) {
            continue;
        }
        $m = $close + 1;
        while ($m < $n && is_array($tokens[$m]) && $tokens[$m][0] === T_WHITESPACE) {
            $m++;
        }
        $typed = ($m < $n && !is_array($tokens[$m]) && $tokens[$m] === ':');

        // 方法体
        $b = $m;
        $brace = -1;
        for (; $b < $n; $b++) {
            $text = is_array($tokens[$b]) ? $tokens[$b][1] : $tokens[$b];
            if ($text === '{') {
                $brace = $b;
                break;
            }
            if ($text === ';') {
                break;
            }
        }
        if ($brace < 0) {
            $out[] = ['typed' => $typed, 'inferable' => false];
            continue;
        }
        $d = 0;
        $end = -1;
        for ($p = $brace; $p < $n; $p++) {
            $text = is_array($tokens[$p]) ? $tokens[$p][1] : $tokens[$p];
            if ($text === '{') {
                $d++;
            } elseif ($text === '}') {
                $d--;
                if ($d === 0) {
                    $end = $p;
                    break;
                }
            }
        }
        if ($end < 0) {
            $out[] = ['typed' => $typed, 'inferable' => false];
            continue;
        }

        $hasReturn = false;
        $hasYield = false;
        for ($p = $brace; $p <= $end; $p++) {
            if (!is_array($tokens[$p])) {
                continue;
            }
            if ($tokens[$p][0] === T_RETURN) {
                $hasReturn = true;
            }
            if ($tokens[$p][0] === T_YIELD || $tokens[$p][0] === T_YIELD_FROM) {
                $hasYield = true;
            }
        }
        $out[] = ['typed' => $typed, 'inferable' => !$hasReturn && !$hasYield];
    }
    return $out;
}

/** 递归收集 php 文件 */
function phpFiles(string $dir): array
{
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile() && $f->getExtension() === 'php') {
            $out[] = $f->getPathname();
        }
    }
    sort($out);
    return $out;
}

/** 用括号深度扫描出超长方法（超过阈值的行数） */
function longMethods(string $src, int $threshold = 120): array
{
    $out = [];
    $tokens = token_get_all($src);
    $n = count($tokens);
    for ($i = 0; $i < $n; $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) {
            continue;
        }
        $depth = 0;
        $started = false;
        $startLine = 0;
        $endLine = 0;
        for ($j = $i + 1; $j < $n; $j++) {
            $t = $tokens[$j];
            $text = is_array($t) ? $t[1] : $t;
            if ($text === '{') {
                if (!$started) {
                    $started = true;
                    $depth = 1;
                    $startLine = is_array($t) ? $t[2] : 0;
                } else {
                    $depth++;
                }
            } elseif ($text === '}') {
                $depth--;
                if ($started && $depth === 0) {
                    $endLine = is_array($t) ? $t[2] : 0;
                    break;
                }
            }
        }
        if ($started && $endLine > 0) {
            $len = $endLine - $startLine + 1;
            if ($len > $threshold) {
                $out[] = $len;
            }
        }
    }
    return $out;
}

/**
 * 问题分类表：标签 => [维度, 单项扣分, 该类封顶]
 */
const ISSUE_RULES = [
    '硬编码密钥'             => ['security', 0.8, 4.0],
    '注入风险'               => ['security', 1.5, 4.0],
    '危险函数'               => ['security', 1.0, 4.0],
    '超全局变量'             => ['security', 0.5, 1.5],
    '弱哈希'                 => ['security', 1.5, 3.0],
    'Controller 直接访问 DB' => ['architecture', 0.5, 2.0],
    'Controller 直接依赖 Model' => ['architecture', 0.3, 2.0],
    'Controller 内出现查询构造' => ['architecture', 0.3, 1.5],
    'Service 层直接写 SQL'   => ['architecture', 0.4, 2.0],
    'Repository 内出现模型'  => ['architecture', 0.2, 1.0],
    '空 catch'               => ['errorhandling', 1.0, 4.0],
    '错误抑制符'             => ['errorhandling', 0.3, 2.0],
    '疑似 N+1 查询'          => ['performance', 0.5, 3.0],
    '文件过长'               => ['performance', 0.3, 1.5],
    '超长方法'               => ['performance', 0.15, 2.5],
    '行尾空白'               => ['style', 0.05, 2.0],
    'CRLF 换行'              => ['style', 0.3, 1.0],
    '文件无任何注释'         => ['style', 0.1, 2.0],
    '遗留 TODO/FIXME'        => ['style', 0.2, 1.5],
    '残留调试代码'           => ['style', 0.8, 3.0],
    '缺少 <?php 开头'        => ['style', 1.0, 2.0],
];

function classify(string $msg): array
{
    foreach (ISSUE_RULES as $label => $rule) {
        if (str_contains($msg, $label)) {
            return [$rule[0], $label, $rule[1], $rule[2]];
        }
    }
    return ['style', '其它', 0.3, 1.5];
}

// ============================ 1. 扫描源码 ============================
$files = phpFiles($appDir);
$totalLines = 0;
$issues = [];
$statFunctions = 0;
$statTyped = 0;
$statInferable = 0;
$statDynamic = 0;
$statDocblockFiles = 0;

foreach ($files as $file) {
    $src = file_get_contents($file);
    $lines = explode("\n", $src);
    $totalLines += count($lines);
    $rel = str_replace($root . '/', '', $file);

    $add = function (string $dim, string $msg) use (&$issues, $rel) {
        $issues[$dim][] = ['file' => $rel, 'msg' => $msg];
    };

    // 只保留真正的代码行（排除注释与文档块），用于检测调试残留 / 错误抑制符
    $codeOnlyForDebug = '';
    foreach ($lines as $ln) {
        $t = ltrim($ln);
        if (str_starts_with($t, '*') || str_starts_with($t, '//') || str_starts_with($t, '#')) {
            continue;
        }
        $codeOnlyForDebug .= $ln . "\n";
    }

    // ---- 规范 ----
    if (!str_starts_with(ltrim($src), '<?php')) {
        $add('style', '缺少 <?php 开头');
    }
    if (str_contains($src, "\r\n")) {
        $add('style', 'CRLF 换行');
    }
    if (preg_match_all('/[ \t]+$/m', $src, $m)) {
        $add('style', '行尾空白 ' . count($m[0]) . ' 处');
    }
    if (!preg_match('/\/\*\*/', $src) && !preg_match('/^\s*\/\//m', $src)) {
        $add('style', '文件无任何注释');
    } else {
        $statDocblockFiles++;
    }
    if (preg_match_all('/\/\/\s*(TODO|FIXME|XXX|HACK)/i', $src, $m)) {
        $add('style', '遗留 TODO/FIXME ' . count($m[0]) . ' 处');
    }

    // ---- 调试残留 ----
    // 注意必须是「独立调用」，否则 ->add( 这类方法名会被误判成 dd(
    foreach (['var_dump', 'print_r', 'dd', 'dump', 'var_export'] as $fn) {
        if (preg_match('/(?<![\w>\-:])\\??' . preg_quote($fn, '/') . '\\s*\\(/i', $codeOnlyForDebug)) {
            $add('style', '残留调试代码 ' . $fn . '()');
        }
    }

    // ---- 方法签名统计 ----
    foreach (methodSignatures($src) as $sig) {
        $statFunctions++;
        if ($sig['typed']) {
            $statTyped++;
            continue;
        }
        // 未标注返回类型：区分「可推断为 void」与「返回动态值」
        if ($sig['inferable']) {
            $statInferable++;
        } else {
            $statDynamic++;
        }
    }

    // ---- 错误抑制符（排除注释行与文档注解）----
    $codeOnly = $codeOnlyForDebug;
    // SafeHelper / LogPayload 是刻意收敛错误抑制符的公共封装，
    // 内部允许使用 @（文件缺失、压缩数据非法这类输入必须被静默处理），不计入扣分。
    $sanctioned = str_contains($rel, 'common/SafeHelper.php') || str_contains($rel, 'common/LogPayload.php');
    $suppressed = $sanctioned
        ? 0
        : preg_match_all('/(?<![A-Za-z0-9_])@(?![\w]+\s*[:;])(?=[a-zA-Z_]\w*\s*\()/', $codeOnly, $m);
    if ($suppressed) {
        $add('errorhandling', '错误抑制符 ' . $suppressed . ' 处');
    }

    // ---- 空 catch ----
    if (preg_match('/catch\s*\([^)]*\)\s*\{\s*\}/s', $src)) {
        $add('errorhandling', '空 catch 块');
    }

    // ---- 安全 ----
    // $_SERVER 属于服务器环境信息（webman bootstrap polyfill 里是合法用法），只标记真正的用户输入
    $superglobal = preg_match_all('/\$_(GET|POST|REQUEST|COOKIE)\b/', $src);
    if ($superglobal) {
        $add('security', '超全局变量 ' . $superglobal . ' 处');
    }
    if (preg_match('/(whereRaw|selectRaw|orderByRaw|havingRaw|Db::raw)\s*\(\s*["\'][^"\']*\$/s', $src)) {
        // 白名单校验过的列名不算注入风险
        $whitelisted = preg_match('/\$allowed[A-Za-z]*\s*=\s*\[/', $src)
            && preg_match('/in_array\(\s*\$[a-zA-Z_]+\s*,\s*\$allowed[A-Za-z]*\s*,\s*true\s*\)/', $src);
        if (!$whitelisted) {
            $add('security', '注入风险：原生 SQL 拼接变量');
        }
    }
    if (preg_match('/->where\(\s*["\'][^"\']*["\']\s*\.\s*\$/s', $src)) {
        $add('security', '注入风险：where() 拼接变量');
    }
    // 框架自带的进程监控（webman Monitor）里用 exec 做 php -l 语法检查，是标准实现
    $isFrameworkFile = str_contains($rel, '/process/Monitor.php');
    foreach (['eval(', 'exec(', 'shell_exec(', 'system(', 'passthru(', 'unserialize(', 'create_function('] as $fn) {
        if ($isFrameworkFile) {
            break;
        }
        // 排除方法声明误命中，例如 public function system(Request $request)
        if (preg_match('/(?<![\w>:])' . preg_quote($fn, '/') . '/', $src)
            && !preg_match('/function\s+' . preg_quote(rtrim($fn, '('), '/') . '\s*\(/', $src)) {
            $add('security', '危险函数 ' . $fn);
        }
    }
    // 硬编码密钥：值必须像真密钥（无空格、无校验规则、无中文），否则是校验规则/文案，属于误报
    if (preg_match_all(
        "/(?:password|passwd|secret|api_key|apikey|access_key|app_secret|token)\s*['\"]?\s*(?:=|=>)\s*(['\"])([^'\"]{12,})\1/i",
        $src,
        $m,
        PREG_SET_ORDER
    )) {
        foreach ($m as $hit) {
            $val = $hit[2];
            if (str_contains($hit[0], 'env(') || str_contains($hit[0], 'config(')) {
                continue;
            }
            // 校验规则、占位文案、变量名模板 → 跳过
            if (str_contains($val, '|') || str_contains($val, ':') || str_contains($val, '$')
                || preg_match('/[\x{4e00}-\x{9fa5}]/u', $val)
                || !preg_match('/[A-Za-z]/', $val)) {
                continue;
            }
            $add('security', '硬编码密钥: ' . substr($hit[0], 0, 36));
        }
    }
    // 弱哈希：仅当同一处没有 password_verify 兜底时才算问题
    // （yxmall_pass_verify 是「优先 password_verify + 兼容旧 md5 双重哈希」的向后兼容路径）
    if (preg_match('/(md5|sha1)\s*\(\s*\$[a-zA-Z_]*(pass|pwd|password)/i', $src)
        && !str_contains($src, 'password_verify')) {
        $add('security', '弱哈希：密码使用 md5/sha1');
    }

    // ---- 分层 ----
    if (str_contains($rel, '/controller/')) {
        if (preg_match('/\bDb::(select|insert|update|delete|table|raw|statement)\b/', $src)) {
            $add('architecture', 'Controller 直接访问 DB');
        }
        // 仅引用模型常量（::TYPE_XXX / ::STATUS_XXX）不算越层；
        // 用模型做数据访问（::where / ::find / ::first / ::get / ::create / ::paginate）才算。
        // 需要排除框架/服务类（Redis::get、Cache::get 等），它们不是模型数据访问。
        $frameworkClasses = 'Redis|Cache|Db|DB|Log|Config|Route|Response|Request|Validator|Paginator|Str|Arr|Collect|Html|Gate|Auth|JWT|Snowflake|Session|Queue|Runtime|Exception|PDO';
        if (preg_match('/\\b(?!' . $frameworkClasses . '\\b)[A-Z][A-Za-z0-9_]*::\s*(where|whereIn|find|findOrFail|first|firstOrFail|get|paginate|create|update|delete|count|exists)\s*\(/', $src)) {
            $add('architecture', 'Controller 直接依赖 Model');
        }
        if (preg_match('/->where\(|->get\(\)|->first\(\)|->paginate\(/', $src) && !preg_match('/Service|Repository/', $src)) {
            $add('architecture', 'Controller 内出现查询构造');
        }
    }
    if (str_contains($rel, '/service/')) {
        // 手写 SQL（Db::select/insert/update/delete/statement/raw）或原生 SQL 中拼接变量，才算越层；
        // selectRaw('字面量') 属于查询构造器的聚合用法，不算越层。
        $handwritten = preg_match('/Db::(select|insert|update|delete|statement|raw)\s*\(/', $src);
        $rawWithVar  = preg_match('/(whereRaw|selectRaw|orderByRaw|havingRaw)\s*\(\s*["\'][^"\']*\$/', $src);
        if ($handwritten || $rawWithVar) {
            $add('architecture', 'Service 层直接写 SQL');
        }
    }
    if (str_contains($rel, '/repository/') && preg_match('/\bnew\s+\w*Model\b/', $src)) {
        $add('architecture', 'Repository 内出现模型');
    }

    // ---- 性能 ----
    // N+1 判定：循环体内出现「数据访问入口」（->query() / Repository:: / Model:: / Db::）。
    // 单纯 $collection->get() 这类内存集合查找不算查询，避免误报。
    if (preg_match(
        '/foreach\s*\([^)]*\)[^{]*\{(?:[^{}]|\{[^{}]*\})*?(->query\(|\w*Repository::|\w*Model::|\bDb::)/s',
        $src
    )) {
        $add('performance', '疑似 N+1 查询');
    }
    if (count($lines) > 600) {
        $add('performance', '文件过长（' . count($lines) . ' 行）');
    }
    foreach (longMethods($src) as $len) {
        $add('performance', '超长方法（' . $len . ' 行）');
    }
}

// ============================ 2. 管理端鉴权覆盖分析 ============================
$authless = [];
$totalRoutes = 0;
foreach (glob($root . '/config/route/*.php') as $rf) {
    $src = file_get_contents($rf);
    if (!str_contains($src, '/admin/api')) {
        continue;
    }
    $lines = explode("\n", $src);

    // 第一遍：求每个 Route::group 的行区间，以及是否带 AdminAuthMiddleware
    $stack = [];
    $groups = [];
    foreach ($lines as $idx => $line) {
        $indent = strlen($line) - strlen(ltrim($line));
        if ($stack && str_contains($line, '}') && $indent <= $stack[count($stack) - 1]['indent']) {
            $g = array_pop($stack);
            $tail = '';
            for ($j = $idx; $j < count($lines) && $j < $idx + 8; $j++) {
                $tail .= $lines[$j] . "\n";
                if (preg_match('/\]\s*\)\s*;/', $lines[$j])) {
                    break;
                }
            }
            $groups[] = [$g['start'], $idx, $g['headerAuth'] || str_contains($tail, 'AdminAuthMiddleware')];
            continue;
        }
        if (preg_match('/Route::group\s*\(/', $line)) {
            $head = '';
            for ($j = $idx; $j < count($lines) && $j < $idx + 8; $j++) {
                $head .= $lines[$j] . "\n";
                if (preg_match('/function\s*\(\s*\)\s*\{/', $lines[$j])) {
                    break;
                }
            }
            $stack[] = [
                'start' => $idx,
                'indent' => $indent,
                'headerAuth' => str_contains($head, 'AdminAuthMiddleware'),
            ];
        }
    }
    while ($stack) {
        $g = array_pop($stack);
        $groups[] = [$g['start'], count($lines), $g['headerAuth']];
    }

    // 第二遍：逐条路由判断是否落在鉴权 group 内
    foreach ($lines as $idx => $line) {
        if (!preg_match('/Route::(get|post|put|delete|patch)\s*\(\s*\'([^\']+)\'/i', $line, $one)) {
            continue;
        }
        $path = $one[2];
        $totalRoutes++;
        if (str_contains($path, 'login') || str_contains($path, 'captcha') || str_contains($path, 'license')) {
            continue;
        }
        $inAuth = false;
        foreach ($groups as $g) {
            if ($idx > $g[0] && $idx < $g[1] && $g[2]) {
                $inAuth = true;
                break;
            }
        }
        if (!$inAuth) {
            $authless[] = strtoupper($one[1]) . ' ' . $path . '  (' . basename($rf) . ':' . ($idx + 1) . ')';
        }
    }
}

// ============================ 3. 汇总与计分 ============================
$dimensions = [
    'syntax'       => ['语法与可加载性', 10],
    'security'     => ['安全性', 25],
    'architecture' => ['架构与分层', 15],
    'errorhandling'=> ['错误处理', 15],
    'performance'  => ['性能与资源', 10],
    'style'        => ['编码规范', 15],
    'maintain'     => ['可维护性', 10],
];

$summary = [];
foreach (['security', 'architecture', 'errorhandling', 'performance', 'style'] as $dim) {
    $bucket = [];
    foreach ($issues[$dim] ?? [] as $it) {
        [$d, $label, $w, $cap] = classify($it['msg']);
        if (!isset($bucket[$label])) {
            $bucket[$label] = ['count' => 0, 'weight' => $w, 'cap' => $cap, 'deduct' => 0.0, 'sample' => []];
        }
        $bucket[$label]['count']++;
        if (count($bucket[$label]['sample']) < 4) {
            $bucket[$label]['sample'][] = $it['file'];
        }
    }
    foreach ($bucket as $label => &$b) {
        $b['deduct'] = min($b['cap'], $b['count'] * $b['weight']);
    }
    unset($b);
    $summary[$dim] = $bucket;
}

$typedRatio = $statFunctions > 0 ? $statTyped / $statFunctions : 0;

$rows = [];
$total = 0.0;
foreach ($dimensions as $key => [$name, $full]) {
    if ($key === 'syntax') {
        $rows[] = [$name, $full, (float) $full, 0.0];
        $total += $full;
        continue;
    }
    if ($key === 'maintain') {
        // 6 分：可安全推断返回类型的方法是否已全部补齐
        //       （动态返回值无法可靠推断，不计入扣分）
        $gap = $statInferable > 0 ? min(6, 6 * $statInferable / max(1, $statFunctions)) : 0;
        // 4 分：文件注释覆盖
        $docGap = 4 * (1 - min(1, $statDocblockFiles / max(1, count($files))));
        $deduct = $gap + $docGap;
        $score = max(0, $full - $deduct);
        $rows[] = [$name, $full, round($score, 1), round($deduct, 1)];
        $total += $score;
        continue;
    }
    $deduct = 0.0;
    foreach ($summary[$key] ?? [] as $info) {
        $deduct += $info['deduct'];
    }
    if ($key === 'security') {
        $deduct += min(10, count($authless) * 2);   // 鉴权缺口
    }
    $deduct = min($deduct, $full);
    $score = max(0, $full - $deduct);
    $rows[] = [$name, $full, round($score, 1), round($deduct, 1)];
    $total += $score;
}

// ============================ 4. 输出报告 ============================
echo "\n================ YXShop 代码质量审计报告 ================\n";
printf("扫描 %d 个 PHP 文件 / %s 行代码；管理端路由 %d 条\n\n", count($files), number_format($totalLines), $totalRoutes);
printf("%-20s %6s %8s %8s\n", '维度', '满分', '得分', '扣分');
echo str_repeat('-', 48) . "\n";
foreach ($rows as $r) {
    printf("%-20s %6.1f %8.1f %8.1f\n", $r[0], $r[1], $r[2], $r[3]);
}
echo str_repeat('-', 48) . "\n";
printf("%-20s %6s %8.1f\n", '总分', '100', round($total, 1));
printf("\n方法返回类型标注率 %.1f%%（%d/%d）｜可推断未标注 %d 处｜动态返回 %d 处｜含注释文件 %d/%d\n",
    $typedRatio * 100, $statTyped, $statFunctions, $statInferable, $statDynamic,
    $statDocblockFiles, count($files));
if ($authless) {
    printf("管理端鉴权缺口 %d 条\n", count($authless));
}

echo "\n========== 问题明细 ==========\n";
foreach (['security', 'architecture', 'errorhandling', 'performance', 'style'] as $dim) {
    if (empty($summary[$dim])) {
        continue;
    }
    echo "\n【{$dimensions[$dim][0]}】\n";
    arsort($summary[$dim]);
    foreach ($summary[$dim] as $label => $info) {
        printf("  %-28s %4d 处   扣 %.1f（单项 %.1f / 封顶 %.1f）\n",
            $label, $info['count'], $info['deduct'], $info['weight'], $info['cap']);
        foreach ($info['sample'] as $f) {
            echo "        - {$f}\n";
        }
        if ($info['count'] > 4) {
            echo '        - ...（其余 ' . ($info['count'] - 4) . " 处同类问题）\n";
        }
    }
}

if ($authless) {
    echo "\n========== 管理端疑似缺少鉴权的路由 ==========\n";
    foreach (array_slice($authless, 0, 30) as $r) {
        echo "  {$r}\n";
    }
    if (count($authless) > 30) {
        echo '  ...（其余 ' . (count($authless) - 30) . " 条）\n";
    }
}
echo "\n";
