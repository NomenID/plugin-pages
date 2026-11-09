<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the GNU General Public License, version 3 only, with
 * additional attribution terms in the NOTICE file.
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
            'title'       => '内容',
            'description' => '站点文档页面：新建、编辑、发布和删除。',
            'menu'        => '内容',
            'group'       => '内容',
            'order'       => 15,
            'tabs'        => [
                ['id' => 'all', 'label' => '全部', 'handler' => static fn(): string => self::renderList('all')],
                ['id' => 'published', 'label' => '已发布', 'handler' => static fn(): string => self::renderList('published')],
                ['id' => 'draft', 'label' => '草稿', 'handler' => static fn(): string => self::renderList('draft')],
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

    private static function renderEditor(array $page, bool $isNew = false): string
    {
        return self::view('admin-edit', [
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
