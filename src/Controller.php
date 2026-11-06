<?php
namespace Nomen\Plugin\Pages;

use Nomen\Src\ActivityLog;
use Nomen\Src\Brand;
use Nomen\Src\Csrf;
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
            'pages' => Store::all($isAdmin),
            'base' => $base,
            'isAdmin' => $isAdmin,
            'notice' => Session::getFlash('pages_success'),
        ], t('文档'));
    }

    public static function show(string $slug): void
    {
        $isAdmin = Router::isAdmin();
        $page = Store::find($slug, $isAdmin);

        if ($page === null) {
            Response::error(404);
            return;
        }

        self::render('page', [
            'page' => $page,
            'pages' => Store::all($isAdmin),
            'base' => Plugin::basePath(),
            'isAdmin' => $isAdmin,
            'article' => Markdown::render((string)$page['body']),
            'notice' => Session::getFlash('pages_success'),
        ], (string)$page['title']);
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
            $error = t('这个地址已经被占用了');
        }

        if ($error !== null) {
            http_response_code(422);
            self::form($data, true, $error);
            return;
        }

        $pageId = Store::create($data, (int)Session::get('user_id'));

        ActivityLog::record([
            'action'      => 'pages.create',
            'category'    => ActivityLog::CAT_PAGES,
            'target_type' => 'page',
            'target_id'   => (string)$pageId,
            'target_name' => $data['slug'],
            'summary'     => '创建页面「' . $data['title'] . '」',
        ]);

        Session::flash('pages_success', t('页面已创建'));
        Response::redirect(Plugin::basePath() . '/' . $data['slug']);
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
            $error = t('这个地址已经被占用了');
        }

        if ($error !== null) {
            $data['id'] = $page['id'];
            $data['action_slug'] = $page['slug'];
            http_response_code(422);
            self::form($data, false, $error);
            return;
        }

        Store::update((int)$page['id'], $data, (int)Session::get('user_id'));

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

        Session::flash('pages_success', t('页面已保存'));
        Response::redirect(Plugin::basePath() . '/' . $data['slug']);
    }

    public static function destroy(string $slug): void
    {
        self::requireCsrf();

        $page = Store::find($slug, true);
        if ($page === null) {
            Response::error(404);
            return;
        }

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

        Session::flash('pages_success', t('页面已删除'));
        Response::redirect(Plugin::basePath());
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

    public static function sitemap(): void
    {
        $baseUrl = rtrim((string)Brand::get('base_url'), '/');
        $base = Plugin::basePath();

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        echo '  <url><loc>' . self::e($baseUrl . '/') . '</loc></url>' . "\n";
        echo '  <url><loc>' . self::e($baseUrl . $base) . '</loc></url>' . "\n";

        foreach (Store::all(false) as $page) {
            echo '  <url><loc>' . self::e($baseUrl . $base . '/' . $page['slug']) . '</loc>';
            if (!empty($page['updated_at'])) {
                echo '<lastmod>' . self::e(substr((string)$page['updated_at'], 0, 10)) . '</lastmod>';
            }
            echo '</url>' . "\n";
        }

        echo '</urlset>';
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
            ? t('新建页面')
            : t('编辑：{:title}', ['title' => (string)($page['title'] ?? '')]));
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
            return [$data, t('标题不能为空')];
        }
        if (!preg_match('/^[a-z0-9][a-z0-9-]{0,99}$/', $slug)) {
            return [$data, t('地址只能用小写字母、数字和连字符')];
        }
        if (in_array($slug, ['new', 'assets', 'sitemap'], true)) {
            return [$data, t('这个地址被系统占用了')];
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
            Response::error(400, t('请求无效，请重试'));
        }
    }

    private static function cssUrl(): string
    {
        $file = __DIR__ . '/../assets/pages.css';
        $v = is_file($file) ? substr(md5_file($file), 0, 8) : '0';

        return Plugin::basePath() . '/assets/pages?v=' . $v;
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
