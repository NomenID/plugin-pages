<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE file in this repository for the full text.
 * Source: https://github.com/NomenID/plugin-pages
 * Project: https://nomenid.org/w/plugin-pages
 */

namespace Nomen\Plugin\Pages;

/**
 * 目录侧边栏。一个目录一份菜单，条目是「标题 | 目标」，缩进两格表示下一级，
 * 第三列写 hide 就不在公开页面显示。
 */
final class Menu
{
    /** @return array<int, array{label: string, href: string, hidden: bool, children: array}> */
    public static function parse(string $text): array
    {
        $items = [];
        $path = [];

        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = rtrim($line);
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            $indent = strlen($line) - strlen(ltrim($line, " \t"));
            $depth = min(intdiv($indent, 2), count($path));

            $cells = array_map('trim', explode('|', $trimmed));
            $label = (string)($cells[0] ?? '');
            if ($label === '') {
                continue;
            }

            $item = [
                'label'    => $label,
                'href'     => (string)($cells[1] ?? ''),
                'hidden'   => strtolower((string)($cells[2] ?? '')) === 'hide',
                'children' => [],
            ];

            $node = &$items;
            foreach (array_slice($path, 0, $depth) as $index) {
                $node = &$node[$index]['children'];
            }
            $node[] = $item;
            $path = array_merge(array_slice($path, 0, $depth), [count($node) - 1]);
            unset($node);
        }

        return $items;
    }

    public static function toText(array $items, int $depth = 0): string
    {
        $out = '';
        foreach ($items as $item) {
            $line = str_repeat('  ', $depth) . (string)$item['label'];
            if ((string)$item['href'] !== '') {
                $line .= ' | ' . (string)$item['href'];
            }
            if (!empty($item['hidden'])) {
                $line .= ' | hide';
            }
            $out .= $line . PHP_EOL;
            $out .= self::toText($item['children'] ?? [], $depth + 1);
        }

        return $out;
    }

    public static function render(array $items, string $current, string $base): string
    {
        if ($items === []) {
            return '';
        }

        $html = '<ul class="docs-nav__list">';
        foreach ($items as $item) {
            if (!empty($item['hidden'])) {
                continue;
            }

            $href = (string)$item['href'];
            $isUrl = preg_match('#^https?://#i', $href) === 1;
            $url = $href === '' ? '#' : ($isUrl ? $href : $base . '/' . ltrim($href, '/'));
            $slug = $isUrl ? '' : ltrim($href, '/');
            $class = $slug !== '' && $slug === $current ? ' class="is-current"' : '';

            $html .= '<li' . $class . '><a href="' . htmlspecialchars($url) . '">'
                . htmlspecialchars((string)$item['label']) . '</a>'
                . self::render($item['children'] ?? [], $current, $base)
                . '</li>';
        }

        return $html . '</ul>';
    }

    /**
     * auto 模式的边栏：有子页的目录折成一组，只有当前页所在的那条链展开，其余收起。
     * 目录标题链到组内第一个文档（优先 index 页）：没有 JS 就当普通链接，有 JS 时
     * pages.js 会拦下跳转、只做展开收起。
     */
    public static function renderAuto(array $items, string $current, string $base): string
    {
        if ($items === []) {
            return '';
        }

        $html = '<ul class="docs-nav__list">';
        foreach ($items as $item) {
            if (!empty($item['hidden'])) {
                continue;
            }

            $children = $item['children'] ?? [];
            $href = ltrim((string)$item['href'], '/');
            $here = $href !== '' && ($current === $href || str_starts_with($current, $href . '/'));
            $class = $here ? ' class="is-current"' : '';

            $html .= '<li' . $class . '>';
            if ($children === []) {
                $url = $base . '/' . $href;
                $html .= '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars((string)$item['label']) . '</a>';
            } else {
                $first = self::firstHref($children, $href);
                $label = htmlspecialchars((string)$item['label']);
                if ($first !== '') {
                    $label = '<a href="' . htmlspecialchars($base . '/' . $first) . '">' . $label . '</a>';
                }
                $html .= '<details' . ($here ? ' open' : '') . '>'
                    . '<summary>' . $label . '</summary>'
                    . self::renderAuto($children, $current, $base)
                    . '</details>';
            }
            $html .= '</li>';
        }

        return $html . '</ul>';
    }

    /** 组里第一个可点的页面：优先目录的 index，其次按树里的先后 */
    private static function firstHref(array $items, string $dir = ''): string
    {
        if ($dir !== '') {
            foreach ($items as $item) {
                if (empty($item['hidden']) && (string)($item['href'] ?? '') === $dir . '/index') {
                    return (string)$item['href'];
                }
            }
        }

        foreach ($items as $item) {
            if (!empty($item['hidden'])) {
                continue;
            }
            if (!empty($item['children'])) {
                $first = self::firstHref($item['children']);
                if ($first !== '') {
                    return $first;
                }
                continue;
            }
            if ((string)($item['href'] ?? '') !== '') {
                return (string)$item['href'];
            }
        }

        return '';
    }

    /** 把树摊平成「缩进文本」，给后台编辑框用 */
    public static function textOf(?array $items): string
    {
        return $items === null ? '' : rtrim(self::toText($items));
    }
}
