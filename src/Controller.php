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
use Nomen\Src\Csrf;
use Nomen\Src\Hooks;
use Nomen\Src\Response;
use Nomen\Src\Router;
use Nomen\Src\Session;

class Controller
{
    public static function index(): void
    {
        $isAdmin = Router::isAdmin();
        $base = Plugin::basePath();

        self::render('index', [
            'groups' => Store::indexGroups($isAdmin),
            'base' => $base,
            'isAdmin' => $isAdmin,
            'notice' => Session::getFlash('pages_success'),
        ], t('Docs'));
    }

    public static function show(string $slug): void
    {
        $path = trim($slug, '/');
        if ($path === '') {
            self::index();
            return;
        }

        $isAdmin = Router::isAdmin();
        $page = Store::find($path, $isAdmin);

        if ($page === null) {
            // 目录地址：先认 <目录>/index，再回退到目录第一条
            $page = Store::find($path . '/index', $isAdmin);
            if ($page === null) {
                $first = Store::firstInDirectory($path, $isAdmin);
                if ($first !== null && $first !== $path) {
                    Response::redirect(Plugin::basePath() . '/' . $first);
                    return;
                }
                Response::error(404);
                return;
            }
        }

        $pages = Store::all($isAdmin);
        $sidebar = Store::sidebarFor((string)$page['slug'], $isAdmin);

        $toc = [];
        $article = Markdown::render((string)$page['body'], $toc);

        self::render('page', [
            'page' => $page,
            'pages' => $pages,
            'menu' => $sidebar['items'] ?? null,
            'menuMode' => $sidebar['mode'] ?? 'manual',
            'base' => Plugin::basePath(),
            'isAdmin' => $isAdmin,
            'article' => $article,
            'toc' => $toc,
            'panes' => self::hiddenPanes(),
            'crumbs' => Nav::crumbs($page, Plugin::basePath()),
            'pager' => Nav::pager($sidebar['items'] ?? null, (string)$page['slug'], $pages),
            'notice' => Session::getFlash('pages_success'),
        ], (string)$page['title']);
    }

    /** 后台保存某个目录的侧边栏 */
    public static function saveNav(): void
    {
        self::requireCsrf();

        $section = trim((string)($_POST['section'] ?? ''), '/');
        $items = Menu::parse((string)($_POST['items'] ?? ''));
        $sort = trim((string)($_POST['sort_order'] ?? ''));

        Store::saveMenu($section, [
            'title' => mb_substr(trim((string)($_POST['title'] ?? '')), 0, 255),
            'summary' => mb_substr(trim((string)($_POST['summary'] ?? '')), 0, 500),
            'sort_order' => $sort === '' ? null : (int)$sort,
            'mode' => ($_POST['mode'] ?? 'manual') === 'auto' ? 'auto' : 'manual',
            'items' => (string)json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'enabled' => !empty($_POST['enabled']),
        ]);

        Session::flash('pages_success', t('Page saved'));
        Response::redirect('/admin/content?tab=nav&section=' . rawurlencode($section));
    }

    public static function create(): void
    {
        self::form([
            'id' => 0,
            'slug' => '',
            'title' => '',
            'summary' => '',
            'body' => '',
            'status' => 1,
            'sort_order' => 0,
        ], true, null);
    }

    public static function store(): void
    {
        self::requireCsrf();

        [$data, $error] = self::input();
        if ($error === null && Store::slugExists($data['slug'])) {
            $error = t('That address is already taken');
        }

        if ($error !== null) {
            http_response_code(422);
            self::form($data, true, $error);
            return;
        }

        $data = Hooks::filter('pages.content.before_save', $data, null, true);

        $pageId = Store::create($data, (int)Session::get('user_id'));

        Hooks::run('pages.content.saved', [$pageId, $data, true, (int)Session::get('user_id')]);

        ActivityLog::record([
            'action'      => 'pages.create',
            'category'    => ActivityLog::CAT_PAGES,
            'target_type' => 'page',
            'target_id'   => (string)$pageId,
            'target_name' => $data['slug'],
            'summary'     => '创建页面「' . $data['title'] . '」',
        ]);

        Session::flash('pages_success', t('Page created'));
        Response::redirect(self::afterSaveUrl($data['slug']));
    }

    public static function edit(string $slug): void
    {
        $page = Store::find($slug, true);
        if ($page === null) {
            Response::error(404);
            return;
        }

        self::form($page, false, null);
    }

    public static function update(string $slug): void
    {
        self::requireCsrf();

        $page = Store::find($slug, true);
        if ($page === null) {
            Response::error(404);
            return;
        }

        [$data, $error] = self::input();
        if ($error === null && Store::slugExists($data['slug'], (int)$page['id'])) {
            $error = t('That address is already taken');
        }

        if ($error !== null) {
            $data['id'] = $page['id'];
            $data['action_slug'] = $page['slug'];
            http_response_code(422);
            self::form($data, false, $error);
            return;
        }

        $data = Hooks::filter('pages.content.before_save', $data, $page, false);

        Store::update((int)$page['id'], $data, (int)Session::get('user_id'));

        Hooks::run('pages.content.saved', [(int)$page['id'], $data, false, (int)Session::get('user_id')]);

        $before = self::pageFields($page);
        ActivityLog::record([
            'action'      => 'pages.update',
            'category'    => ActivityLog::CAT_PAGES,
            'target_type' => 'page',
            'target_id'   => (string)$page['id'],
            'target_name' => $data['slug'],
            'summary'     => '编辑页面「' . $data['title'] . '」',
            'changes'     => ActivityLog::diff($before, self::pageFields($data), array_keys($before)),
        ]);

        Session::flash('pages_success', t('Page saved'));
        Response::redirect(self::afterSaveUrl($data['slug']));
    }

    public static function destroy(string $slug): void
    {
        self::requireCsrf();

        $page = Store::find($slug, true);
        if ($page === null) {
            Response::error(404);
            return;
        }

        Hooks::run('pages.content.deleted', [(int)$page['id'], $page, (int)Session::get('user_id')]);

        Store::delete((int)$page['id']);

        $before = self::pageFields($page);
        ActivityLog::record([
            'action'      => 'pages.delete',
            'category'    => ActivityLog::CAT_PAGES,
            'target_type' => 'page',
            'target_id'   => (string)$page['id'],
            'target_name' => (string)$page['slug'],
            'summary'     => '删除页面「' . $page['title'] . '」',
            'changes'     => ActivityLog::diff($before, [], array_keys($before)),
        ]);

        Session::flash('pages_success', t('Page deleted'));
        Response::redirect(($_POST['_return'] ?? '') === 'admin' ? '/admin/content?tab=all' : Plugin::basePath());
    }

    private static function afterSaveUrl(string $slug): string
    {
        return ($_POST['_return'] ?? '') === 'admin'
            ? '/admin/content?tab=all'
            : Plugin::basePath() . '/' . $slug;
    }

    public static function css(): void
    {
        $file = __DIR__ . '/../assets/pages.css';
        if (!is_file($file)) {
            Response::error(404);
            return;
        }

        header('Content-Type: text/css; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        readfile($file);
        exit;
    }

    public static function js(): void
    {
        $file = __DIR__ . '/../assets/pages.js';
        if (!is_file($file)) {
            Response::error(404);
            return;
        }

        header('Content-Type: text/javascript; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        readfile($file);
        exit;
    }

    public static function sitemap(): void
    {
        $xml = Sitemap::xml();
        $etag = '"' . md5($xml) . '"';

        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        header('ETag: ' . $etag);

        $ifNoneMatch = trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
        // nginx 开了 gzip 会把 ETag 改成弱校验值（W/"..."），比较前先去掉前缀
        if (str_starts_with($ifNoneMatch, 'W/')) {
            $ifNoneMatch = substr($ifNoneMatch, 2);
        }
        if ($ifNoneMatch !== '' && $ifNoneMatch === $etag) {
            http_response_code(304);
            return;
        }

        echo $xml;
        exit;
    }

    private static function form(array $page, bool $isNew, ?string $error): void
    {
        self::render('edit', [
            'page' => $page,
            'isNew' => $isNew,
            'error' => $error,
            'base' => Plugin::basePath(),
        ], $isNew
            ? t('New page')
            : t('Edit: {:title}', ['title' => (string)($page['title'] ?? '')]));
    }

    private static function render(string $template, array $data, string $title): void
    {
        extract($data);
        ob_start();
        require __DIR__ . '/../templates/' . $template . '.php';
        $body = (string)ob_get_clean();

        Response::view('special', [
            'title' => $title,
            'hideTitle' => true,
            'pageClass' => 'special-page--docs',
            'body' => $body,
            'extraCss' => [self::cssUrl()],
            'extraJs' => self::jsUrl(),
        ]);
    }

    /** @return array<string, string> */
    private static function pageFields(array $page): array
    {
        return [
            'title'      => (string)($page['title'] ?? ''),
            'slug'       => (string)($page['slug'] ?? ''),
            'summary'    => (string)($page['summary'] ?? ''),
            'status'     => (int)($page['status'] ?? 0) === 1 ? '已发布' : '草稿',
            'sort_order' => (string)($page['sort_order'] ?? 0),
            'body'       => (string)($page['body'] ?? ''),
        ];
    }

    /** @return array{0: array, 1: string|null} */
    private static function input(): array
    {
        $title = trim(self::post('title'));
        $slug = Store::makeSlug(self::post('slug'));
        if ($slug === '') {
            $slug = Store::makeSlug($title);
        }

        $summary = trim(self::post('summary'));
        $body = self::post('body');
        $status = self::post('status', '1') === '0' ? 0 : 1;
        $sortOrder = (int)self::post('sort_order', '0');

        $data = [
            'slug' => $slug,
            'title' => mb_substr($title, 0, 255),
            'summary' => mb_substr($summary, 0, 500),
            'body' => $body,
            'status' => $status,
            'sort_order' => $sortOrder,
        ];

        if ($title === '') {
            return [$data, t('Title is required')];
        }
        if (!preg_match('/^[a-z0-9][a-z0-9-]{0,99}$/', $slug)) {
            return [$data, t('The address may only use lowercase letters, digits, and hyphens')];
        }
        if (in_array($slug, ['new', 'assets', 'sitemap'], true)) {
            return [$data, t('That address is reserved')];
        }

        return [$data, null];
    }

    private static function post(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    private static function requireCsrf(): void
    {
        $token = $_POST['_csrf'] ?? null;
        if (!is_string($token) || !Csrf::validate($token)) {
            Response::error(400, t('Invalid request, try again'));
        }
    }

    /**
     * 侧栏折叠状态由页面脚本写进 cookie，这里读回来先渲染好，避免刷新时闪一下。
     *
     * @return array{nav: bool, toc: bool}
     */
    private static function hiddenPanes(): array
    {
        $raw = $_COOKIE['nomen_docs_panes'] ?? '';
        $raw = is_string($raw) ? $raw : '';
        $hidden = explode('-', $raw);

        return [
            'nav' => in_array('nav', $hidden, true),
            'toc' => in_array('toc', $hidden, true),
        ];
    }

    private static function cssUrl(): string
    {
        $file = __DIR__ . '/../assets/pages.css';
        $v = is_file($file) ? substr(md5_file($file), 0, 8) : '0';

        return Plugin::basePath() . '/assets/pages?v=' . $v;
    }

    private static function jsUrl(): string
    {
        $file = __DIR__ . '/../assets/pages.js';
        $v = is_file($file) ? substr(md5_file($file), 0, 8) : '0';

        return Plugin::basePath() . '/assets/pages-js?v=' . $v;
    }
}
