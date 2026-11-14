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
 * 阅读导航：面包屑、本页目录、上一页 / 下一页。
 * 上一页 / 下一页跟着侧边栏的顺序走（auto 与手写菜单都摊平成叶子列表），
 * 没挂侧边栏的页面退化成同目录按 sort_order 排。
 */
final class Nav
{
    /** @return array<int, array{label:string, href:?string}> */
    public static function crumbs(array $page, string $base): array
    {
        $slug = trim((string)($page['slug'] ?? ''), '/');
        $segments = $slug === '' ? [] : explode('/', $slug);
        array_pop($segments);

        // 目录的 index 页本身就是这个目录，别再重复一级
        if ($slug === 'index' || str_ends_with($slug, '/index')) {
            array_pop($segments);
        }

        $trail = [['label' => t('Docs'), 'href' => $base]];
        $path = '';
        foreach ($segments as $segment) {
            $path = $path === '' ? $segment : $path . '/' . $segment;
            $trail[] = ['label' => Store::labelFor($path), 'href' => $base . '/' . $path];
        }
        $trail[] = ['label' => (string)($page['title'] ?? $slug), 'href' => null];

        return $trail;
    }

    /** @param array<int, array{level:int,id:string,text:string}> $toc */
    public static function tocList(array $toc): string
    {
        if ($toc === []) {
            return '';
        }

        $html = '<ul class="docs-toc__list">';
        foreach ($toc as $item) {
            $html .= '<li class="docs-toc__level-' . (int)$item['level'] . '">'
                . '<a href="#' . htmlspecialchars((string)$item['id'], ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars((string)$item['text'], ENT_QUOTES, 'UTF-8') . '</a></li>';
        }

        return $html . '</ul>';
    }

    /**
     * @param array|null $items 侧边栏条目（auto 或手写，形状一样）
     * @param array $pages Store::all() 的结果
     * @return array{prev: ?array{slug:string,label:string}, next: ?array{slug:string,label:string}}
     */
    public static function pager(?array $items, string $current, array $pages): array
    {
        $titles = [];
        $order = [];
        foreach ($pages as $row) {
            $titles[(string)$row['slug']] = (string)$row['title'];
        }

        self::leaves($items ?? [], $titles, $order);
        $index = array_search($current, $order, true);

        // 手写菜单漏了这一页时退回同目录排序，总比一个链接都没有强
        if ($index === false) {
            $order = self::siblings($current, $pages, $titles);
            $index = array_search($current, $order, true);
        }
        if ($index === false) {
            return ['prev' => null, 'next' => null];
        }

        $make = static fn (string $slug): array => ['slug' => $slug, 'label' => $titles[$slug] ?? $slug];

        return [
            'prev' => isset($order[$index - 1]) ? $make($order[$index - 1]) : null,
            'next' => isset($order[$index + 1]) ? $make($order[$index + 1]) : null,
        ];
    }

    /**
     * 把侧边栏树摊平成叶子页面。目录节点本身不进上一页 / 下一页，
     * hidden 条目跳过，手写菜单里的外链和无效地址也跳过。
     *
     * @param array<string, string> $titles
     * @param array<int, string> $out
     */
    private static function leaves(array $items, array $titles, array &$out): void
    {
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (!empty($item['hidden'])) {
                continue;
            }

            // 手写菜单里一个条目可以既是页面又有子条目；auto 的目录节点 href 是目录地址，
            // 在 $titles 里查不到，自然跳过，不会把目录当成页面
            $slug = ltrim((string)($item['href'] ?? ''), '/');
            if ($slug !== '' && isset($titles[$slug])) {
                $out[] = $slug;
            }

            $children = $item['children'] ?? [];
            if (is_array($children) && $children !== []) {
                self::leaves($children, $titles, $out);
            }
        }
    }

    /**
     * @param array<string, string> $titles
     * @return array<int, string>
     */
    private static function siblings(string $current, array $pages, array $titles): array
    {
        $dir = str_contains($current, '/') ? substr($current, 0, strrpos($current, '/')) : '';
        $prefix = $dir === '' ? '' : $dir . '/';

        $rows = [];
        foreach ($pages as $row) {
            $slug = (string)$row['slug'];
            if ($prefix !== '' && !str_starts_with($slug, $prefix)) {
                continue;
            }
            if (str_contains(substr($slug, strlen($prefix)), '/')) {
                continue;
            }
            $rows[] = [(int)($row['sort_order'] ?? 0), $titles[$slug] ?? $slug, $slug];
        }

        usort($rows, static fn (array $a, array $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        return array_map(static fn (array $row): string => $row[2], $rows);
    }
}
