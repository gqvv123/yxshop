<?php
/**
 * 为可安全推断的方法补返回类型（保守版）：
 *   - 无 return 语句且不是生成器 → : void
 *   - 所有 return 均为同一种字面量 → 对应标量类型（int/float/string/bool/array）
 *   - 其余情况一律不动（宁可少标也不标错）
 *
 * 跳过：魔术方法（__construct 等不允许声明返回类型）、生成器（yield）、抽象/接口方法、
 *       已声明返回类型的方法、闭包与箭头函数。
 *
 * 用法: php scripts/add_return_types.php [--dry]
 */

$root = dirname(__DIR__);
$dry = in_array('--dry', $argv, true);

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

/** 不允许声明返回类型的魔术方法 */
const MAGIC_NO_TYPE = [
    '__construct', '__destruct', '__clone', '__sleep', '__wakeup',
    '__serialize', '__unserialize', '__set_state', '__toString',
];

function inferTypes(string $src): array
{
    $tokens = token_get_all($src);
    $n = count($tokens);

    // 计算每个 token 的起始字节偏移
    $offsets = [];
    $pos = 0;
    foreach ($tokens as $i => $t) {
        $offsets[$i] = $pos;
        $pos += strlen(is_array($t) ? $t[1] : $t);
    }

    $edits = [];   // [byteOffsetOfCloseParen, type]

    for ($i = 0; $i < $n; $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) {
            continue;
        }
        // 下一段：函数名
        $j = $i + 1;
        while ($j < $n && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            $j++;
        }
        if ($j >= $n) {
            continue;
        }
        if (!is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING) {
            continue;   // 闭包 / 箭头函数
        }
        $methodName = $tokens[$j][1];
        if (in_array($methodName, MAGIC_NO_TYPE, true)) {
            continue;
        }

        // 找参数列表右括号
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

        // 已有返回类型？
        $m = $close + 1;
        while ($m < $n && is_array($tokens[$m]) && $tokens[$m][0] === T_WHITESPACE) {
            $m++;
        }
        if ($m < $n && !is_array($tokens[$m]) && $tokens[$m] === ':') {
            continue;   // 已声明返回类型
        }

        // 找方法体
        $b = $m;
        $brace = -1;
        for (; $b < $n; $b++) {
            $text = is_array($tokens[$b]) ? $tokens[$b][1] : $tokens[$b];
            if ($text === '{') {
                $brace = $b;
                break;
            }
            if ($text === ';') {
                break;   // 抽象/接口方法
            }
        }
        if ($brace < 0) {
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
            continue;
        }

        // 扫描方法体：是否生成器 / return 类型
        $hasYield = false;
        $hasReturn = false;
        $types = [];
        $parenDepth = 0;
        for ($p = $brace; $p <= $end; $p++) {
            $t = $tokens[$p];
            if (is_array($t)) {
                if ($t[0] === T_YIELD || $t[0] === T_YIELD_FROM) {
                    $hasYield = true;
                }
                if ($t[0] === T_RETURN) {
                    $hasReturn = true;
                    $q = $p + 1;
                    while ($q < $n && is_array($tokens[$q]) && $tokens[$q][0] === T_WHITESPACE) {
                        $q++;
                    }
                    if ($q < $n) {
                        $qt = $tokens[$q];
                        $txt = is_array($qt) ? $qt[1] : $qt;
                        if (is_array($qt) && $qt[0] === T_STRING) {
                            $lower = strtolower($txt);
                            if ($lower === 'true' || $lower === 'false') {
                                $types['bool'] = true;
                            } elseif ($lower === 'null') {
                                $types['null'] = true;
                            } else {
                                $types['__dynamic__'] = true;
                            }
                        } elseif (is_array($qt) && $qt[0] === T_LNUMBER) {
                            $types['int'] = true;
                        } elseif (is_array($qt) && $qt[0] === T_DNUMBER) {
                            $types['float'] = true;
                        } elseif (is_array($qt) && $qt[0] === T_CONSTANT_ENCAPSED_STRING) {
                            $types['string'] = true;
                        } elseif ($txt === '[') {
                            $types['array'] = true;
                        } else {
                            $types['__dynamic__'] = true;
                        }
                    } else {
                        $types['__dynamic__'] = true;
                    }
                }
                continue;
            }
        }
        if ($hasYield) {
            continue;   // 生成器不能标 void
        }

        $type = null;
        if (!$hasReturn) {
            $type = 'void';
        } elseif (!isset($types['__dynamic__']) && !isset($types['null']) && count($types) === 1) {
            $type = array_key_first($types);
        }
        if ($type === null) {
            continue;
        }

        // 在右括号之后插入 ': type'
        $closeOffset = $offsets[$close] + 1;   // ')' 的结束位置
        $edits[] = [$closeOffset, ': ' . $type];
    }

    // 按偏移倒序应用
    usort($edits, fn($a, $b) => $b[0] <=> $a[0]);
    foreach ($edits as [$off, $text]) {
        $src = substr($src, 0, $off) . $text . substr($src, $off);
    }
    return [$src, count($edits)];
}

$files = collectPhp($root . '/app');
$total = 0;
$changed = 0;
foreach ($files as $file) {
    $src = file_get_contents($file);
    [$new, $n] = inferTypes($src);
    if ($n > 0 && $new !== $src) {
        // 语法校验：不通过就不写
        $tmp = tempnam(sys_get_temp_dir(), 'rt');
        file_put_contents($tmp, $new);
        exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $out, $rc);
        unlink($tmp);
        if ($rc !== 0) {
            echo "[跳过] 语法校验失败: {$file}\n";
            echo '  ' . implode("\n  ", array_slice($out, 0, 3)) . "\n";
            continue;
        }
        $total += $n;
        $changed++;
        if (!$dry) {
            file_put_contents($file, $new);
        }
    }
}
printf("%s 补返回类型 %d 处，涉及 %d 个文件\n", $dry ? '[DRY] 将' : '已', $total, $changed);
