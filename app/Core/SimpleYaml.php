<?php

declare(strict_types=1);

namespace App\Core;

/**
 * 极简 YAML 解析器 —— 仅支持 wikicode workflow schema 用到的语法：
 *   - 嵌套 map（key: value）
 *   - 列表（- item，或 - key: value 后接嵌套）
 *   - 字符串 / 数字 / 布尔 / null
 *   - 行内注释（# 开头，忽略）
 *   - 单 / 双引号字符串
 *
 * 不支持：锚点、多行字符串、复杂嵌套、inline array / map。
 */
final class SimpleYaml
{
    public static function parse(string $text): array
    {
        $lines = [];
        foreach (explode("\n", $text) as $raw) {
            $line = rtrim($raw);
            // 跳过空行 / 注释行
            if ($line === '' || preg_match('/^\s*#/', $line)) continue;
            $lines[] = $line;
        }
        $pos = 0;
        return self::parseBlock($lines, $pos, 0);
    }

    /**
     * @param string[] $lines
     */
    private static function parseBlock(array $lines, int &$pos, int $indent): mixed
    {
        if ($pos >= count($lines)) return null;

        $line = $lines[$pos];
        $curIndent = self::indentOf($line);
        if ($curIndent < $indent) return null;

        // 列表块
        if (preg_match('/^(\s*)- /', $line)) {
            return self::parseList($lines, $pos, $curIndent);
        }
        // map 块
        return self::parseMap($lines, $pos, $curIndent);
    }

    private static function parseMap(array $lines, int &$pos, int $indent): array
    {
        $result = [];
        while ($pos < count($lines)) {
            $line = $lines[$pos];
            $curIndent = self::indentOf($line);
            if ($curIndent < $indent) break;
            if ($curIndent > $indent) {
                // 上一项的嵌套已被消费，跳过
                $pos++;
                continue;
            }
            if (!preg_match('/^(\s*)([A-Za-z_][\w\-\.]*)\s*:\s*(.*)$/', $line, $m)) {
                $pos++;
                continue;
            }
            $key = $m[2];
            $value = trim($m[3]);
            // 去掉行内注释（不在引号内的 # 后内容）
            $value = self::stripComment($value);
            $pos++;
            if ($value === '') {
                // 值在下一行（嵌套块）
                $nested = self::parseBlock($lines, $pos, $indent + 1);
                $result[$key] = $nested;
            } else {
                $result[$key] = self::parseScalar($value);
            }
        }
        return $result;
    }

    private static function parseList(array $lines, int &$pos, int $indent): array
    {
        $result = [];
        while ($pos < count($lines)) {
            $line = $lines[$pos];
            $curIndent = self::indentOf($line);
            if ($curIndent < $indent) break;
            if (!preg_match('/^(\s*)- (.*)$/', $line, $m)) break;
            if (strlen($m[1]) !== $indent) break;
            $item = trim($m[2]);
            $pos++;
            // - key: value （列表项是 map）
            if (preg_match('/^([A-Za-z_][\w\-\.]*)\s*:\s*(.*)$/', $item, $mm)) {
                $obj = [];
                $k = $mm[1];
                $v = self::stripComment(trim($mm[2]));
                if ($v === '') {
                    $obj[$k] = self::parseBlock($lines, $pos, $indent + 2);
                } else {
                    $obj[$k] = self::parseScalar($v);
                }
                // 同一 list item 后续缩进的 key: value
                while ($pos < count($lines)) {
                    $next = $lines[$pos];
                    $nextIndent = self::indentOf($next);
                    if ($nextIndent <= $indent) break;
                    if (!preg_match('/^(\s*)([A-Za-z_][\w\-\.]*)\s*:\s*(.*)$/', $next, $nm)) break;
                    $k2 = $nm[2];
                    $v2 = self::stripComment(trim($nm[3]));
                    $pos++;
                    if ($v2 === '') {
                        $obj[$k2] = self::parseBlock($lines, $pos, $nextIndent + 1);
                    } else {
                        $obj[$k2] = self::parseScalar($v2);
                    }
                }
                $result[] = $obj;
            } else {
                $result[] = self::parseScalar(self::stripComment($item));
            }
        }
        return $result;
    }

    private static function parseScalar(string $value): mixed
    {
        if ($value === '' || $value === '~' || $value === 'null') return null;
        if ($value === 'true') return true;
        if ($value === 'false') return false;
        // 引号字符串
        if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            return substr($value, 1, -1);
        }
        // inline array: [a, b, c]
        if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
            $inner = trim(substr($value, 1, -1));
            if ($inner === '') return [];
            return array_map(static fn ($s) => self::parseScalar(trim($s)), explode(',', $inner));
        }
        // 数字
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }
        return $value;
    }

    private static function indentOf(string $line): int
    {
        return strlen($line) - strlen(ltrim($line, ' '));
    }

    private static function stripComment(string $value): string
    {
        // 引号内的 # 不算注释
        $inSingle = false;
        $inDouble = false;
        $len = strlen($value);
        for ($i = 0; $i < $len; $i++) {
            $ch = $value[$i];
            if ($ch === "'" && !$inDouble) $inSingle = !$inSingle;
            elseif ($ch === '"' && !$inSingle) $inDouble = !$inDouble;
            elseif ($ch === '#' && !$inSingle && !$inDouble) {
                // # 前面必须是空白或开头
                if ($i === 0 || ctype_space($value[$i - 1])) {
                    return trim(substr($value, 0, $i));
                }
            }
        }
        return trim($value);
    }
}
