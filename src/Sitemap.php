<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE file in this repository for the full text.
 * Source: https://github.com/NomenID/plugin-pages
 * Project: https://nomenid.org/w/plugin-pages
 */

namespace Nomen\Plugin\Pages;

use Nomen\Src\Brand;

/**
 * 文档站点的 sitemap：按库里的已发布页面现场生成，不需要手工维护。
 *
 * 目录的 index 页对外规范地址是目录本身（/w/local-docs），
 * 只有该目录同时存在同名页面时才保留 /w/local-docs/index。
 */
final class Sitemap
{
    /**
     * @return array<int, array{loc: string, lastmod: string}>
     */
    public static function entries(): array
    {
        $baseUrl = rtrim((string)Brand::get('base_url'), '/');
        $base = Plugin::basePath();
        $pages = Store::all(false);

        $slugs = [];
        foreach ($pages as $page) {
            $slugs[(string)$page['slug']] = true;
        }

        $urls = [];
        $latest = '';

        foreach ($pages as $page) {
            $slug = (string)$page['slug'];
            $lastmod = substr((string)($page['updated_at'] ?? ''), 0, 10);
            $latest = max($latest, $lastmod);

            $path = $slug;
            if (str_ends_with($slug, '/index')) {
                $dir = substr($slug, 0, -6);
                if (!isset($slugs[$dir])) {
                    $path = $dir;
                }
            }

            $urls[$path] = max($urls[$path] ?? '', $lastmod);
        }

        $entries = [
            ['loc' => $baseUrl . '/', 'lastmod' => ''],
            ['loc' => $baseUrl . $base, 'lastmod' => $latest],
        ];

        foreach ($urls as $path => $lastmod) {
            $entries[] = ['loc' => $baseUrl . $base . '/' . $path, 'lastmod' => $lastmod];
        }

        return $entries;
    }

    public static function xml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach (self::entries() as $entry) {
            $xml .= '  <url><loc>' . self::escape($entry['loc']) . '</loc>';
            if ($entry['lastmod'] !== '') {
                $xml .= '<lastmod>' . self::escape($entry['lastmod']) . '</lastmod>';
            }
            $xml .= '</url>' . "\n";
        }

        return $xml . '</urlset>' . "\n";
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
