<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE file in this repository for the full text.
 * Source: https://github.com/NomenID/plugin-pages
 * Project: https://nomenid.org/w/plugin-pages
 */

namespace Nomen\Plugin\Pages;

use Nomen\Src\ActivityLog;
use Nomen\Src\Admin as AdminRegistry;
use Nomen\Src\Csrf;
use Nomen\Src\Hooks;
use Nomen\Src\Session;

class Admin
{
    public static function register(): void
    {
        AdminRegistry::page('content', [
            'title'       => 'Content',
            'description' => 'Site documentation pages: create, edit, publish and delete.',
            'menu'        => 'Content',
            'group'       => 'Content',
            'order'       => 15,
            'tabs'        => [
                ['id' => 'all', 'label' => 'All', 'handler' => static fn(): string => self::renderList('all')],
                ['id' => 'published', 'label' => 'Published', 'handler' => static fn(): string => self::renderList('published')],
                ['id' => 'draft', 'label' => 'Drafts', 'handler' => static fn(): string => self::renderList('draft')],
                ['id' => 'nav', 'label' => 'Sidebars', 'handler' => static fn(): string => self::renderNav()],
            ],
        ]);
    }

    public static function renderList(string $status): string
    {
        $all = Store::all(true);
        $pages = array_values(array_filter($all, static function (array $page) use ($status): bool {
            if ($status === 'published') {
                return (int)$page['status'] === 1;
            }
            if ($status === 'draft') {
                return (int)$page['status'] !== 1;
            }
            return true;
        }));

        $edit = isset($_GET['edit']) ? (string)$_GET['edit'] : '';
        if ($edit === 'new') {
            return self::renderEditor([
                'id' => 0,
                'slug' => '',
                'title' => '',
                'summary' => '',
                'body' => '',
                'status' => 1,
                'sort_order' => 0,
                'updated_at' => '',
            ], true);
        }
        if ($edit !== '') {
            $page = Store::find($edit, true);
            if ($page !== null) {
                return self::renderEditor($page);
            }
        }

        return self::view('admin-list', [
            'pages'  => $pages,
            'counts' => [
                'all'       => count($all),
                'published' => count(array_filter($all, static fn(array $p): bool => (int)$p['status'] === 1)),
                'draft'     => count(array_filter($all, static fn(array $p): bool => (int)$p['status'] !== 1)),
            ],
            'status' => $status,
            'notice' => (string)(Session::getFlash('pages_success') ?: ''),
            'base'   => Plugin::basePath(),
        ]);
    }

    /** 每个目录一份侧边栏；没挂菜单的目录不显示侧边栏 */
    public static function renderNav(): string
    {
        $sections = Store::sections(true);
        $section = trim((string)($_GET['section'] ?? ''), '/');
        if (!in_array($section, $sections, true)) {
            $section = '';
        }

        $row = Store::menuRow($section);
        $items = $row === null ? null : json_decode((string)$row['items'], true);

        return self::view('admin-nav', [
            'sections' => $sections,
            'section'  => $section,
            'text'     => Menu::textOf(is_array($items) ? $items : null),
            'enabled'  => $row === null ? false : (int)$row['enabled'] === 1,
            'title'    => $row === null ? '' : (string)$row['title'],
            'summary'  => $row === null ? '' : (string)$row['summary'],
            'sort'     => $row === null || $row['sort_order'] === null ? '' : (string)$row['sort_order'],
            'collapsed' => $row !== null && (int)$row['collapsed'] === 1,
            'mode'     => $row === null ? 'manual' : (string)$row['mode'],
            'base'     => Plugin::basePath(),
            'csrf'     => Csrf::field(),
            'pages'    => Store::all(true),
            'notice'   => (string)(Session::getFlash('pages_success') ?: ''),
        ]);
    }

    private static function renderEditor(array $page, bool $isNew = false): string
    {        return self::view('admin-edit', [
            'page'  => $page,
            'isNew' => $isNew,
            'base'  => Plugin::basePath(),
            'csrf'  => Csrf::field(),
            'error' => (string)(Session::getFlash('pages_error') ?: ''),
            'extras' => self::tabExtras($page),
        ]);
    }

    private static function view(string $template, array $data): string
    {
        extract($data);
        ob_start();
        require __DIR__ . '/../templates/' . $template . '.php';
        return (string)ob_get_clean();
    }

    /** 供其它插件/主题在内容后台里追加一块区域 */
    public static function tabExtras(array $page): string
    {
        return (string)Hooks::filter('pages.admin.editor.extras', '', $page);
    }
}
