<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE file in this repository for the full text.
 * Source: https://github.com/NomenID/plugin-pages
 * Project: https://nomenid.org/w/plugin-pages
 */

namespace Nomen\Plugin\Pages;

use Nomen\Src\Cache;

/**
 * 够用就行的 Markdown：标题、段落、列表、引用、代码块、表格、链接、图片、强调。
 * HTML 一律转义，链接只放行 http(s)、mailto、站内相对地址。
 */
class Markdown
{
    // 渲染规则改了就把版本 +1，别让旧 HTML 继续命中
    private const CACHE_VERSION = '1';

    private const CACHE_TTL = 7 * 24 * 3600;

    /** @var array<int, array{level:int,id:string,text:string}> 最近一次 render 收集到的标题 */
    private static array $toc = [];

    /** @var array<string, true> 本次渲染里已用过的锚点，重名标题加 -1 / -2 */
    private static array $anchors = [];

    /**
     * @param array<int, array{level:int,id:string,text:string}>|null $toc 传变量进来就填本页的 h2 / h3
     */
    public static function render(string $text, ?array &$toc = null): string
    {
        $text = str_replace(["\r\n", "\r", "\x00"], ["\n", "\n", ''], $text);
        $key = 'pages:md:v' . self::CACHE_VERSION . ':' . sha1($text);

        $cached = Cache::remember($key, self::CACHE_TTL, static function () use ($text): array {
            $built = [];
            $html = self::renderNow($text, $built);

            return ['html' => $html, 'toc' => $built];
        });

        if (is_array($cached) && isset($cached['html'])) {
            if ($toc !== null) {
                $toc = is_array($cached['toc'] ?? null) ? $cached['toc'] : [];
            }

            return (string)$cached['html'];
        }

        return self::renderNow($text, $toc);
    }

    /** @param array<int, array{level:int,id:string,text:string}>|null $toc */
    private static function renderNow(string $text, ?array &$toc = null): string
    {
        self::$toc = [];
        self::$anchors = [];

        $html = self::blocks(explode("\n", $text));

        if ($toc !== null) {
            $toc = self::$toc;
        }

        return $html;
    }

    private static function blocks(array $lines): string
    {
        $out = [];
        $i = 0;
        $n = count($lines);

        while ($i < $n) {
            $line = $lines[$i];

            if (trim($line) === '') {
                $i++;
                continue;
            }

            if (preg_match('/^\s{0,3}```([\w#+.-]*)\s*$/', $line, $m)) {
                $lang = $m[1];
                $i++;
                $code = [];
                while ($i < $n && !preg_match('/^\s{0,3}```\s*$/', $lines[$i])) {
                    $code[] = $lines[$i];
                    $i++;
                }
                if ($i < $n) {
                    $i++;
                }
                $out[] = '<pre><code' . ($lang !== '' ? ' class="language-' . self::e($lang) . '"' : '') . '>'
                    . self::e(implode("\n", $code)) . '</code></pre>';
                continue;
            }

            if (preg_match('/^\s{0,3}([-*_])(?:\s*\1){2,}\s*$/', $line)) {
                $out[] = '<hr>';
                $i++;
                continue;
            }

            if (preg_match('/^\s{0,3}(#{1,6})\s+(.*?)\s*#*\s*$/', $line, $m)) {
                $level = strlen($m[1]);
                $plain = self::plain($m[2]);
                $id = self::anchor($plain);
                if ($level === 2 || $level === 3) {
                    self::$toc[] = ['level' => $level, 'id' => $id, 'text' => $plain];
                }
                $out[] = '<h' . $level . ' id="' . $id . '">' . self::inline($m[2]) . '</h' . $level . '>';
                $i++;
                continue;
            }

            if (preg_match('/^\s{0,3}>\s?(.*)$/', $line, $m)) {
                $quote = [];
                while ($i < $n && preg_match('/^\s{0,3}>\s?(.*)$/', $lines[$i], $m)) {
                    $quote[] = $m[1];
                    $i++;
                }
                $out[] = '<blockquote>' . self::blocks($quote) . '</blockquote>';
                continue;
            }

            if (self::isTableStart($lines, $i)) {
                $out[] = self::table($lines, $i);
                continue;
            }

            if (self::isListLine($line)) {
                $out[] = self::listBlock($lines, $i);
                continue;
            }

            $para = [];
            while ($i < $n && trim($lines[$i]) !== '' && !self::startsBlock($lines, $i)) {
                $para[] = ltrim($lines[$i]);
                $i++;
            }
            if ($para === []) {
                $para[] = trim($line);
                $i++;
            }
            $out[] = '<p>' . self::inline(implode("\n", $para)) . '</p>';
        }

        return implode("\n", $out);
    }

    /** 标题的纯文本：去掉链接、图片、行内代码和强调符号，给锚点和目录当文字 */
    private static function plain(string $text): string
    {
        $text = preg_replace('/!\[([^\]]*)\]\([^)]*\)/u', '$1', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\([^)]*\)/u', '$1', $text) ?? $text;
        $text = preg_replace('/`([^`]*)`/u', '$1', $text) ?? $text;
        $text = preg_replace('/\*\*(.+?)\*\*|__(.+?)__/u', '$1$2', $text) ?? $text;
        $text = preg_replace('/\*(.+?)\*|_(.+?)_/u', '$1$2', $text) ?? $text;
        $text = preg_replace('/~~(.+?)~~/u', '$1', $text) ?? $text;

        return trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /** 锚点：小写、去标点、空白转连字符，重名的按 -1 / -2 递增 */
    private static function anchor(string $text): string
    {
        $id = mb_strtolower($text, 'UTF-8');
        $id = preg_replace('/[^\p{L}\p{N}\s_-]+/u', '', $id) ?? $id;
        $id = preg_replace('/[\s_-]+/u', '-', $id) ?? $id;
        $id = trim($id, '-');
        if ($id === '') {
            $id = 'section';
        }

        $base = $id;
        $n = 0;
        while (isset(self::$anchors[$id])) {
            $n++;
            $id = $base . '-' . $n;
        }
        self::$anchors[$id] = true;

        return $id;
    }

    private static function startsBlock(array $lines, int $i): bool
    {
        $line = $lines[$i];

        return (bool)(
            preg_match('/^\s{0,3}```/', $line)
            || preg_match('/^\s{0,3}(#{1,6})\s+/', $line)
            || preg_match('/^\s{0,3}([-*_])(?:\s*\1){2,}\s*$/', $line)
            || preg_match('/^\s{0,3}>\s?/', $line)
            || self::isListLine($line)
            || self::isTableStart($lines, $i)
        );
    }

    private static function isListLine(string $line): bool
    {
        return (bool)preg_match('/^\s{0,3}([-*+]|\d+[.)])\s+\S/', $line);
    }

    private static function isTableStart(array $lines, int $i): bool
    {
        if (!isset($lines[$i + 1]) || !str_contains($lines[$i], '|') || !str_contains($lines[$i + 1], '|')) {
            return false;
        }

        $head = self::row($lines[$i]);
        $sep = self::row($lines[$i + 1]);
        if ($head === [] || count($head) !== count($sep)) {
            return false;
        }

        foreach ($sep as $cell) {
            if (!preg_match('/^:?-{3,}:?$/', $cell)) {
                return false;
            }
        }

        return true;
    }

    private static function table(array $lines, int &$i): string
    {
        $head = self::row($lines[$i]);
        $i += 2;

        $body = [];
        $n = count($lines);
        while ($i < $n && trim($lines[$i]) !== '' && str_contains($lines[$i], '|')) {
            $body[] = self::row($lines[$i]);
            $i++;
        }

        $html = '<div class="md-table"><table><thead><tr>';
        foreach ($head as $cell) {
            $html .= '<th>' . self::inline($cell) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($body as $row) {
            $html .= '<tr>';
            foreach ($head as $idx => $_) {
                $html .= '<td>' . self::inline($row[$idx] ?? '') . '</td>';
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table></div>';
    }

    /** @return string[] */
    private static function row(string $line): array
    {
        $line = trim($line);
        $line = preg_replace('/^\|/', '', $line) ?? $line;
        $line = preg_replace('/\|$/', '', $line) ?? $line;

        $cells = [];
        $current = '';
        $inCode = false;
        $len = strlen($line);

        for ($i = 0; $i < $len; $i++) {
            $char = $line[$i];

            if ($char === '`') {
                $inCode = !$inCode;
                $current .= $char;
                continue;
            }
            if ($char === '\\' && $i + 1 < $len && $line[$i + 1] === '|') {
                $current .= '|';
                $i++;
                continue;
            }
            if ($char === '|' && !$inCode) {
                $cells[] = trim($current);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $cells[] = trim($current);

        return $cells;
    }

    private static function listBlock(array $lines, int &$i): string
    {
        $n = count($lines);
        $ordered = (bool)preg_match('/^\s{0,3}\d+[.)]\s+/', $lines[$i]);
        $tag = $ordered ? 'ol' : 'ul';
        $items = [];

        while ($i < $n && preg_match('/^(\s{0,3})([-*+]|\d+[.)])\s+(.*)$/', $lines[$i], $m)) {
            if (ctype_digit($m[2][0]) !== $ordered) {
                break;
            }

            $markerIndent = strlen($m[1]);
            $contentIndent = $markerIndent + strlen($m[2]) + 1;
            $item = [$m[3]];
            $i++;

            while ($i < $n) {
                $next = $lines[$i];

                if (trim($next) === '') {
                    if ($i + 1 < $n && preg_match('/^\s{2,}\S/', $lines[$i + 1])) {
                        $item[] = '';
                        $i++;
                        continue;
                    }
                    break;
                }

                if (preg_match('/^(\s*)([-*+]|\d+[.)])\s+/', $next, $nm)) {
                    $indent = strlen($nm[1]);
                    if ($indent <= $markerIndent) {
                        break;
                    }
                    $item[] = substr($next, $indent);
                    $i++;
                    continue;
                }

                if (preg_match('/^\s{2,}\S/', $next)) {
                    $leading = strlen($next) - strlen(ltrim($next));
                    $item[] = substr($next, min($leading, $contentIndent));
                    $i++;
                    continue;
                }

                $item[] = trim($next);
                $i++;
            }

            $items[] = '<li>' . self::blocks($item) . '</li>';
        }

        return '<' . $tag . '>' . implode('', $items) . '</' . $tag . '>';
    }

    private static function inline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $tokens = [];
        $stash = static function (string $html) use (&$tokens): string {
            $key = "\x01" . count($tokens) . "\x01";
            $tokens[$key] = $html;

            return $key;
        };

        $text = preg_replace_callback(
            '/`([^`\n]+)`/',
            static fn(array $m): string => $stash('<code>' . $m[1] . '</code>'),
            $text
        ) ?? $text;

        $text = preg_replace_callback(
            '/!\[([^\]]*)\]\(([^)\s]+)(?:\s+&quot;(.*?)&quot;)?\)/',
            static function (array $m) use ($stash): string {
                $url = self::url($m[2]);
                if ($url === null) {
                    return '![' . $m[1] . '](' . $m[2] . ')';
                }
                $title = isset($m[3]) && $m[3] !== '' ? ' title="' . $m[3] . '"' : '';

                return $stash('<img src="' . $url . '" alt="' . $m[1] . '"' . $title . '>');
            },
            $text
        ) ?? $text;

        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)\s]+)(?:\s+&quot;(.*?)&quot;)?\)/',
            static function (array $m) use ($stash): string {
                $url = self::url($m[2]);
                if ($url === null) {
                    return '[' . $m[1] . '](' . $m[2] . ')';
                }
                $title = isset($m[3]) && $m[3] !== '' ? ' title="' . $m[3] . '"' : '';

                return $stash('<a href="' . $url . '"' . $title . ' rel="noopener">' . $m[1] . '</a>');
            },
            $text
        ) ?? $text;

        $text = preg_replace_callback(
            '~(?<![\w"/])(https?://[A-Za-z0-9\-._\~:/?#\[\]@!$&\'()*+,;=%]+)~',
            static function (array $m) use ($stash): string {
                $url = rtrim($m[1], '.,!?)。，；：！？');
                $tail = substr($m[1], strlen($url));
                $href = self::url($url);

                return $href === null
                    ? $m[1]
                    : $stash('<a href="' . $href . '" rel="noopener">' . $url . '</a>') . $tail;
            },
            $text
        ) ?? $text;

        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/(?<!\*)\*(?!\s)(.+?)(?<!\s)\*(?!\*)/s', '<em>$1</em>', $text) ?? $text;
        $text = preg_replace('/~~(.+?)~~/s', '<del>$1</del>', $text) ?? $text;

        $text = preg_replace('/ {2,}\n[ \t]*/', '<br>', $text) ?? $text;
        $text = str_replace("\n", ' ', $text);

        return strtr($text, $tokens);
    }

    private static function url(string $url): ?string
    {
        $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $url)) {
            if (!preg_match('/^(https?|mailto):/i', $url)) {
                return null;
            }
        } elseif (str_starts_with($url, '//')) {
            return null;
        }

        return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
