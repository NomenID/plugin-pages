<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the GNU General Public License, version 3 only.
 * Attribution requests are in the NOTICE file.
 * Source: https://github.com/NomenID/plugin-pages
 * Project: https://nomenid.org/w/plugin-pages
 */

namespace Nomen\Plugin\Pages;

use Nomen\Src\Api as CoreApi;
use Nomen\Src\Brand;

/**
 * 把文档页面接到 /nomen-api：list=allpages、prop=info、prop=content。
 * 草稿默认不出去，带 drafts=1 时要管理员。
 */
final class Api
{
    public static function boot(): void
    {
        CoreApi::list('allpages', [self::class, 'allPages'], 'public', '列出文档页面（limit / offset / drafts）');
        CoreApi::prop('info', [self::class, 'info'], 'public', '页面元信息（titles / drafts）');
        CoreApi::prop('content', [self::class, 'content'], 'public', '页面正文，Markdown 原文（titles / drafts）');
    }

    public static function allPages(array $params): array
    {
        $limit = self::intParam($params, 'limit', 50, 1, 500);
        $offset = self::intParam($params, 'offset', 0, 0, 1000000);
        $drafts = self::drafts($params);

        if ($drafts) {
            CoreApi::requireAdmin();
        }

        $pages = Store::all($drafts);
        $total = count($pages);
        $items = [];

        foreach (array_slice($pages, $offset, $limit) as $page) {
            $items[] = self::item($page);
        }

        $result = ['allpages' => $items];
        if ($offset + $limit < $total) {
            $result['continue'] = ['offset' => $offset + $limit];
        }

        return $result;
    }

    public static function info(array $titles, array $params): array
    {
        $drafts = self::drafts($params);

        if ($drafts) {
            CoreApi::requireAdmin();
        }

        $out = [];
        foreach ($titles as $slug) {
            $page = Store::find($slug, $drafts);
            if ($page !== null) {
                $out[$slug] = self::item($page);
            }
        }

        return $out;
    }

    public static function content(array $titles, array $params): array
    {
        $drafts = self::drafts($params);

        if ($drafts) {
            CoreApi::requireAdmin();
        }

        $out = [];
        foreach ($titles as $slug) {
            $page = Store::find($slug, $drafts);
            if ($page !== null) {
                $out[$slug] = [
                    'content' => (string)$page['body'],
                    'contentmodel' => 'markdown',
                ];
            }
        }

        return $out;
    }

    private static function item(array $page): array
    {
        return [
            'slug' => (string)$page['slug'],
            'title' => (string)$page['title'],
            'summary' => (string)($page['summary'] ?? ''),
            'url' => rtrim((string)Brand::get('base_url'), '/')
                . Plugin::basePath() . '/' . (string)$page['slug'],
            'status' => (int)($page['status'] ?? 1) === 1 ? 'published' : 'draft',
            'sort_order' => (int)($page['sort_order'] ?? 0),
            'updated_at' => (string)($page['updated_at'] ?? ''),
        ];
    }

    private static function drafts(array $params): bool
    {
        $value = (string)($params['drafts'] ?? '0');
        if (!in_array($value, ['0', '1'], true)) {
            CoreApi::error(400, 'badvalue', 'drafts must be 0 or 1.');
        }

        return $value === '1';
    }

    private static function intParam(array $params, string $key, int $default, int $min, int $max): int
    {
        if (!isset($params[$key]) || $params[$key] === '') {
            return $default;
        }

        $value = filter_var($params[$key], FILTER_VALIDATE_INT);
        if ($value === false || $value < $min || $value > $max) {
            CoreApi::error(400, 'badvalue', $key . ' must be an integer between ' . $min . ' and ' . $max . '.');
        }

        return $value;
    }
}
