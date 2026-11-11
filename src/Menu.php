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

    /** 把树摊平成「缩进文本」，给后台编辑框用 */
    public static function textOf(?array $items): string
    {
        return $items === null ? '' : rtrim(self::toText($items));
    }
}
