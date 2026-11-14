<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE file in this repository for the full text.
 * Source: https://github.com/NomenID/plugin-pages
 * Project: https://nomenid.org/w/plugin-pages
 */


namespace Nomen\Plugin\Pages;

use Nomen\Src\Cli\Sql;
use Nomen\Src\Database;
use PDO;

class Store
{
    private static bool $ready = false;

    public static function db(): PDO
    {
        $db = Database::getInstance();

        if (self::$ready) {
            return $db;
        }

        $dialect = Database::dialectFor($db);
        $exists = $dialect->tableExists($db, 'pages');

        $ddl = $dialect->isSqlite()
            ? "CREATE TABLE IF NOT EXISTS pages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT NOT NULL COLLATE NOCASE,
                title TEXT NOT NULL,
                summary TEXT NOT NULL DEFAULT '',
                body TEXT NOT NULL,
                status INTEGER NOT NULL DEFAULT 1,
                sort_order INTEGER NOT NULL DEFAULT 0,
                updated_by INTEGER DEFAULT NULL,
                created_at TEXT NOT NULL DEFAULT '',
                updated_at TEXT NOT NULL DEFAULT ''
            );
            CREATE UNIQUE INDEX IF NOT EXISTS idx_pages_slug ON pages (slug);
            CREATE INDEX IF NOT EXISTS idx_pages_status_order ON pages (status, sort_order);
            CREATE TRIGGER IF NOT EXISTS pages__insert_defaults AFTER INSERT ON pages
            BEGIN
              UPDATE pages SET
                created_at = CASE WHEN NEW.created_at IS NULL OR NEW.created_at = '' THEN datetime('now','localtime') ELSE NEW.created_at END,
                updated_at = CASE WHEN NEW.updated_at IS NULL OR NEW.updated_at = '' THEN datetime('now','localtime') ELSE NEW.updated_at END
              WHERE rowid = NEW.rowid;
            END;
            CREATE TRIGGER IF NOT EXISTS pages__updated_at AFTER UPDATE ON pages
            WHEN NEW.updated_at = OLD.updated_at
            BEGIN
              UPDATE pages SET updated_at = datetime('now','localtime') WHERE rowid = NEW.rowid;
            END;"
            : 'CREATE TABLE IF NOT EXISTS `pages` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `slug` VARCHAR(191) NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `summary` VARCHAR(500) NOT NULL DEFAULT \'\',
                `body` MEDIUMTEXT NOT NULL,
                `status` TINYINT UNSIGNED NOT NULL DEFAULT 1,
                `sort_order` INT NOT NULL DEFAULT 0,
                `updated_by` INT UNSIGNED DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_pages_slug` (`slug`),
                KEY `idx_pages_status_order` (`status`, `sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        foreach (Sql::splitStatements($ddl) as $statement) {
            $db->exec($statement);
        }

        $menuDdl = $dialect->isSqlite()
            ? 'CREATE TABLE IF NOT EXISTS page_menus (
                path TEXT PRIMARY KEY,
                title TEXT NOT NULL DEFAULT \'\',
                summary TEXT NOT NULL DEFAULT \'\',
                sort_order INTEGER DEFAULT NULL,
                mode TEXT NOT NULL DEFAULT \'manual\',
                items TEXT NOT NULL DEFAULT \'\',
                enabled INTEGER NOT NULL DEFAULT 1,
                updated_at TEXT NOT NULL DEFAULT \'\'
            )'
            : 'CREATE TABLE IF NOT EXISTS `page_menus` (
                `path` VARCHAR(191) NOT NULL,
                `title` VARCHAR(255) NOT NULL DEFAULT \'\',
                `summary` VARCHAR(500) NOT NULL DEFAULT \'\',
                `sort_order` INT DEFAULT NULL,
                `mode` VARCHAR(10) NOT NULL DEFAULT \'manual\',
                `items` MEDIUMTEXT NOT NULL,
                `enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`path`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        $db->exec($menuDdl);
        self::ensureMenuColumns($db, $dialect);

        self::$ready = true;

        if (!$exists) {
            self::seed($db);
        }

        return $db;
    }

    /** @return array<int, array> */
    public static function all(bool $includeDrafts = false): array
    {
        $sql = 'SELECT id, slug, title, summary, status, sort_order, updated_at FROM pages';
        if (!$includeDrafts) {
            $sql .= ' WHERE status = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';

        return self::db()->query($sql)->fetchAll();
    }

    public static function find(string $slug, bool $includeDrafts = false): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM pages WHERE slug = :slug' . ($includeDrafts ? '' : ' AND status = 1') . ' LIMIT 1'
        );
        $stmt->execute([':slug' => $slug]);
        $page = $stmt->fetch();

        return $page ?: null;
    }

    public static function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM pages WHERE slug = :slug';
        $params = [':slug' => $slug];

        if ($ignoreId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $ignoreId;
        }

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }

    public static function create(array $data, int $userId): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO pages (slug, title, summary, body, status, sort_order, updated_by)
             VALUES (:slug, :title, :summary, :body, :status, :sort_order, :updated_by)'
        );
        $stmt->execute([
            ':slug' => $data['slug'],
            ':title' => $data['title'],
            ':summary' => $data['summary'],
            ':body' => $data['body'],
            ':status' => $data['status'],
            ':sort_order' => $data['sort_order'],
            ':updated_by' => $userId,
        ]);

        return (int)self::db()->lastInsertId();
    }

    public static function update(int $id, array $data, int $userId): void
    {
        $stmt = self::db()->prepare(
            'UPDATE pages
             SET slug = :slug, title = :title, summary = :summary, body = :body,
                 status = :status, sort_order = :sort_order, updated_by = :updated_by, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':slug' => $data['slug'],
            ':title' => $data['title'],
            ':summary' => $data['summary'],
            ':body' => $data['body'],
            ':status' => $data['status'],
            ':sort_order' => $data['sort_order'],
            ':updated_by' => $userId,
            ':id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM pages WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function makeSlug(string $value): string
    {
        $value = strtolower(trim($value));
        $segments = [];

        foreach (explode('/', $value) as $segment) {
            $segment = preg_replace('/[^a-z0-9-]+/', '-', $segment) ?? '';
            $segment = trim($segment, '-');
            if ($segment !== '') {
                $segments[] = $segment;
            }
        }

        return substr(implode('/', $segments), 0, 191);
    }

    /** 只补缺，不覆盖已经在库里改过的页面。 */
    public static function sync(): int
    {
        return self::seed(self::db());
    }

    private static function seed(PDO $db): int
    {
        $files = glob(__DIR__ . '/../content/*.md') ?: [];
        sort($files);
        $inserted = 0;

        foreach ($files as $file) {
            $raw = (string)file_get_contents($file);
            [$meta, $body] = self::frontmatter($raw);

            $slug = self::makeSlug((string)($meta['slug'] ?? basename($file, '.md')));
            if ($slug === '') {
                continue;
            }

            $title = trim((string)($meta['title'] ?? str_replace('-', ' ', ucfirst($slug))));
            if ($title === '') {
                continue;
            }

            $stmt = $db->prepare(
                Database::dialectFor($db)->insertIgnore(
                    'INSERT IGNORE INTO pages (slug, title, summary, body, status, sort_order)
                     VALUES (:slug, :title, :summary, :body, 1, :sort_order)'
                )
            );
            $stmt->execute([
                ':slug' => $slug,
                ':title' => $title,
                ':summary' => trim((string)($meta['summary'] ?? '')),
                ':body' => $body,
                ':sort_order' => (int)($meta['sort'] ?? 0),
            ]);
            $inserted += $stmt->rowCount();
        }

        return $inserted;
    }

    /**
     * 文档首页的条目：顶层页面各自一项，子目录合成一项（优先用目录记录，其次用 index 页当标题）。
     *
     * @return array<int, array{type: string, title: string, summary: string, status: int, count: int, href: string}>
     */
    public static function indexGroups(bool $includeDrafts = false): array
    {
        $groups = [];

        foreach (self::all($includeDrafts) as $page) {
            $slug = (string)$page['slug'];
            $pos = strpos($slug, '/');

            if ($pos === false) {
                $groups['p:' . $slug] = [
                    'type' => 'page',
                    'title' => (string)$page['title'],
                    'summary' => (string)$page['summary'],
                    'status' => (int)$page['status'],
                    'count' => 1,
                    'href' => $slug,
                    'sort' => (int)$page['sort_order'],
                ];
                continue;
            }

            $dir = substr($slug, 0, $pos);
            $key = 'd:' . $dir;
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'type' => 'dir', 'title' => self::humanize($dir), 'summary' => '', 'status' => 1,
                    'count' => 0, 'href' => $dir, 'sort' => PHP_INT_MAX,
                ];
            }
            $groups[$key]['count']++;

            if ($slug === $dir . '/index') {
                $groups[$key]['title'] = (string)$page['title'];
                $groups[$key]['summary'] = (string)$page['summary'];
                $groups[$key]['status'] = (int)$page['status'];
                $groups[$key]['sort'] = (int)$page['sort_order'];
            } else {
                $groups[$key]['sort'] = min($groups[$key]['sort'], (int)$page['sort_order']);
            }
        }

        foreach ($groups as $key => $group) {
            if ($group['type'] !== 'dir') {
                continue;
            }
            $row = self::menuRow(substr($key, 2));
            if ($row === null) {
                continue;
            }
            if (trim((string)$row['title']) !== '') {
                $groups[$key]['title'] = (string)$row['title'];
            }
            if (trim((string)$row['summary']) !== '') {
                $groups[$key]['summary'] = (string)$row['summary'];
            }
            if ($row['sort_order'] !== null) {
                $groups[$key]['sort'] = (int)$row['sort_order'];
            }
        }

        $out = array_values($groups);
        usort($out, static fn(array $a, array $b): int => [$a['sort'], $a['title']] <=> [$b['sort'], $b['title']]);

        return $out;
    }

    /** @return array{0: array<string, string>, 1: string} */
    private static function frontmatter(string $raw): array
    {
        if (!preg_match('/^---\R(.*?)\R---\R?(.*)$/su', $raw, $m)) {
            return [[], $raw];
        }

        $meta = [];
        foreach (preg_split('/\R/u', $m[1]) ?: [] as $line) {
            if (preg_match('/^([A-Za-z0-9_-]+):\s*(.*)$/u', $line, $kv)) {
                $meta[$kv[1]] = trim($kv[2], " \t\"'");
            }
        }

        return [$meta, $m[2]];
    }

    /**
     * 目录清单：从已有 slug 推导出来。'' 表示根目录（/w 下面那一层）。
     *
     * @return array<int, string>
     */
    public static function sections(bool $includeDrafts = true): array
    {
        $out = [''];

        foreach (self::all($includeDrafts) as $page) {
            $dir = (string)$page['slug'];
            while (($pos = strrpos($dir, '/')) !== false) {
                $dir = substr($dir, 0, $pos);
                $out[$dir] = $dir;
            }
        }

        // 页删光了但目录记录还在，后台也得能进去改
        foreach (self::db()->query('SELECT path FROM page_menus')->fetchAll(PDO::FETCH_COLUMN) as $path) {
            $path = trim((string)$path, '/');
            if ($path !== '') {
                $out[$path] = $path;
            }
        }

        sort($out);

        return array_values(array_unique($out));
    }

    /** 某个目录挂了菜单就返回条目树，没挂返回 null */
    public static function menu(string $path): ?array
    {
        $row = self::menuRow($path);
        if ($row === null || (int)$row['enabled'] !== 1) {
            return null;
        }

        $items = json_decode((string)$row['items'], true);

        return is_array($items) ? $items : null;
    }

    /** 后台编辑用：不管启用与否都取出来 */
    public static function menuRow(string $path): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM page_menus WHERE path = :p LIMIT 1');
        $stmt->execute([':p' => $path]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function saveMenu(string $path, array $fields): void
    {
        $db = self::db();
        $params = [
            ':p' => $path,
            ':title' => (string)($fields['title'] ?? ''),
            ':summary' => (string)($fields['summary'] ?? ''),
            ':sort_order' => $fields['sort_order'] ?? null,
            ':mode' => ($fields['mode'] ?? 'manual') === 'auto' ? 'auto' : 'manual',
            ':items' => (string)($fields['items'] ?? ''),
            ':enabled' => !empty($fields['enabled']) ? 1 : 0,
        ];

        $assign = 'title = :title, summary = :summary, sort_order = :sort_order,'
            . ' mode = :mode, items = :items, enabled = :enabled';

        if (self::menuRow($path) !== null) {
            $stmt = $db->prepare(
                'UPDATE page_menus SET ' . $assign . ', updated_at = ' . Database::dialectFor($db)->now() . ' WHERE path = :p'
            );
            $stmt->execute($params);
            return;
        }

        $stmt = $db->prepare(
            'INSERT INTO page_menus (path, title, summary, sort_order, mode, items, enabled)
             VALUES (:p, :title, :summary, :sort_order, :mode, :items, :enabled)'
        );
        $stmt->execute($params);
    }

    /**
     * 当前页面该用哪份侧边栏：从自己所在目录往上找第一份启用的目录记录。
     * auto 按 slug 树现场生成，manual 用后台写的条目。
     *
     * @return array{path: string, mode: string, items: array}|null
     */
    public static function sidebarFor(string $slug, bool $includeDrafts = false): ?array
    {
        $dir = str_contains($slug, '/') ? substr($slug, 0, strrpos($slug, '/')) : '';

        while (true) {
            $row = self::menuRow($dir);
            if ($row !== null && (int)$row['enabled'] === 1) {
                if ((string)($row['mode'] ?? 'manual') === 'auto') {
                    $items = self::autoMenu($dir, $includeDrafts);
                    if ($items !== []) {
                        return ['path' => $dir, 'mode' => 'auto', 'items' => $items];
                    }
                } else {
                    $items = json_decode((string)$row['items'], true);
                    if (is_array($items)) {
                        return ['path' => $dir, 'mode' => 'manual', 'items' => $items];
                    }
                }
            }
            if ($dir === '') {
                return null;
            }
            $dir = str_contains($dir, '/') ? substr($dir, 0, strrpos($dir, '/')) : '';
        }
    }

    /** 目录首页：先找 <目录>/index，再按菜单顺序 / 排序取第一条 */
    public static function firstInDirectory(string $dir, bool $includeDrafts = false): ?string
    {
        $dir = trim($dir, '/');
        $prefix = $dir === '' ? '' : $dir . '/';

        if (self::find($prefix . 'index', $includeDrafts) !== null) {
            return $prefix . 'index';
        }

        $row = self::menuRow($dir);
        if ($row !== null && (int)$row['enabled'] === 1 && (string)($row['mode'] ?? 'manual') === 'auto') {
            return self::firstHref(self::autoMenu($dir, $includeDrafts));
        }

        $menu = $dir === '' ? null : self::menu($dir);
        if ($menu !== null) {
            foreach (self::menuHrefs($menu) as $href) {
                if (str_starts_with($href, $prefix) && self::find($href, $includeDrafts) !== null) {
                    return $href;
                }
            }
        }

        foreach (self::all($includeDrafts) as $page) {
            $slug = (string)$page['slug'];
            if (!str_starts_with($slug, $prefix)) {
                continue;
            }
            $rest = substr($slug, strlen($prefix));
            if ($rest !== '' && !str_contains($rest, '/')) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * auto 模式：把某个目录下的页面按 slug 树拼成侧边栏。
     * 目录标题 / 排序取目录记录，没有就退回 index 页标题和小写连字符转空格。
     *
     * @return array<int, array>
     */
    public static function autoMenu(string $dir, bool $includeDrafts = false): array
    {
        return self::autoTree(trim($dir, '/'), $includeDrafts);
    }

    /** @return array<int, array> */
    private static function autoTree(string $dir, bool $includeDrafts): array
    {
        $prefix = $dir === '' ? '' : $dir . '/';
        $items = [];
        $dirs = [];

        foreach (self::all($includeDrafts) as $page) {
            $slug = (string)$page['slug'];
            if ($prefix !== '' && !str_starts_with($slug, $prefix)) {
                continue;
            }

            $rest = substr($slug, strlen($prefix));
            if ($rest === '') {
                continue;
            }
            if (str_contains($rest, '/')) {
                $dirs[substr($rest, 0, strpos($rest, '/'))] = true;
                continue;
            }

            $items[] = [
                'label' => (string)$page['title'],
                'href' => $slug,
                'hidden' => false,
                'group' => false,
                'children' => [],
                'sort' => (int)$page['sort_order'],
            ];
        }

        foreach (array_keys($dirs) as $segment) {
            $path = $prefix . $segment;
            $children = self::autoTree($path, $includeDrafts);
            if ($children === []) {
                continue;
            }

            $row = self::menuRow($path);
            $label = $row === null ? '' : trim((string)$row['title']);
            $summary = $row === null ? '' : trim((string)$row['summary']);
            $sort = $row === null || $row['sort_order'] === null ? null : (int)$row['sort_order'];

            if ($label === '') {
                $index = self::find($path . '/index', $includeDrafts);
                $label = $index !== null ? (string)$index['title'] : self::humanize($segment);
            }
            if ($sort === null) {
                $sort = min(array_map(static fn(array $item): int => $item['sort'], $children));
            }

            $items[] = [
                'label' => $label,
                'summary' => $summary,
                'href' => $path,
                'hidden' => false,
                'group' => true,
                'children' => $children,
                'sort' => $sort,
            ];
        }

        usort($items, static fn(array $a, array $b): int => [$a['sort'], $a['label']] <=> [$b['sort'], $b['label']]);

        return $items;
    }

    /** @return string|null 自动菜单里第一个可点的页面 */
    private static function firstHref(array $items): ?string
    {
        foreach ($items as $item) {
            if (!empty($item['children'])) {
                $found = self::firstHref($item['children']);
                if ($found !== null) {
                    return $found;
                }
                continue;
            }
            if ((string)$item['href'] !== '') {
                return (string)$item['href'];
            }
        }

        return null;
    }

    /** 目录的显示名：目录记录 > 目录的 index 页标题 > slug 连字符转空格 */
    public static function labelFor(string $path, bool $includeDrafts = false): string
    {
        $path = trim($path, '/');
        if ($path === '') {
            return t('Docs');
        }

        $row = self::menuRow($path);
        if ($row !== null && trim((string)$row['title']) !== '') {
            return trim((string)$row['title']);
        }

        $index = self::find($path . '/index', $includeDrafts);
        if ($index !== null) {
            return (string)$index['title'];
        }

        return self::humanize(basename($path));
    }

    private static function humanize(string $segment): string
    {
        return ucfirst(str_replace('-', ' ', $segment));
    }

    /** @return array<int, string> */
    private static function menuHrefs(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            $href = (string)($item['href'] ?? '');
            if ($href !== '' && !preg_match('#^https?://#i', $href)) {
                $out[] = ltrim($href, '/');
            }
            $out = array_merge($out, self::menuHrefs($item['children'] ?? []));
        }

        return $out;
    }

    private static function ensureMenuColumns(PDO $db, \Nomen\Src\Db\Dialect $dialect): void
    {
        $existing = self::columnsOf($db, $dialect, 'page_menus');
        $columns = [
            'title' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'summary' => "VARCHAR(500) NOT NULL DEFAULT ''",
            'sort_order' => 'INT DEFAULT NULL',
            'mode' => "VARCHAR(10) NOT NULL DEFAULT 'manual'",
        ];

        foreach ($columns as $name => $definition) {
            if (in_array($name, $existing, true)) {
                continue;
            }
            $db->exec('ALTER TABLE page_menus ADD COLUMN ' . $name . ' ' . $definition);
        }
    }

    /** @return array<int, string> */
    private static function columnsOf(PDO $db, \Nomen\Src\Db\Dialect $dialect, string $table): array
    {
        if ($dialect->isSqlite()) {
            $rows = $db->query('PRAGMA table_info(' . $table . ')')->fetchAll();

            return array_map(static fn(array $col): string => (string)($col['name'] ?? ''), $rows);
        }

        $stmt = $db->prepare(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
        );
        $stmt->execute([$table]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * 从目录导入 Markdown：`install.md` → `<prefix>/install`，
     * 目录根的 README.md → `<prefix>/index`。只补缺，不覆盖库里改过的页面。
     */
    public static function import(string $dir, string $prefix, bool $update = false): int
    {
        $base = realpath($dir);
        if ($base === false || !is_dir($base)) {
            return -1;
        }

        $prefix = trim($prefix, '/');
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'md') {
                $files[] = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
            }
        }
        sort($files);

        $inserted = 0;
        foreach ($files as $rel) {
            $slug = strtolower(preg_replace('/\.md$/i', '', $rel) ?? $rel);
            $slug = $slug === 'readme' ? 'index' : $slug;
            $slug = self::makeSlug($prefix . '/' . $slug);
            if ($slug === '' || $slug === $prefix) {
                continue;
            }

            [$meta, $body] = self::frontmatter((string)file_get_contents($base . '/' . $rel));
            $title = trim((string)($meta['title'] ?? ''));
            if ($title === '' && preg_match('/\A\s*#\s+(.+?)\s*(?:\R+|\z)/u', $body, $m)) {
                $title = trim($m[1]);
                $body = substr($body, strlen($m[0]));
            }
            if ($title === '') {
                $title = trim(basename($rel, '.md'));
            }
            $body = self::rewriteLinks($body, $prefix, $rel);
            if ($title === '') {
                continue;
            }

            $hasSort = isset($meta['sort']) && $meta['sort'] !== '';
            $params = [
                ':slug' => $slug,
                ':title' => $title,
                ':summary' => trim((string)($meta['summary'] ?? '')),
                ':body' => $body,
                ':sort_order' => (int)($meta['sort'] ?? 0),
            ];

            if ($update && self::find($slug, true) !== null) {
                // 文件里没写 sort 就别动站内排好的顺序
                $sql = 'UPDATE pages SET title = :title, summary = :summary, body = :body'
                    . ($hasSort ? ', sort_order = :sort_order' : '')
                    . ', updated_at = NOW() WHERE slug = :slug';
                if (!$hasSort) {
                    unset($params[':sort_order']);
                }
                $stmt = self::db()->prepare($sql);
                $stmt->execute($params);
                $inserted += $stmt->rowCount();
                continue;
            }

            $stmt = self::db()->prepare(
                Database::dialectFor(self::db())->insertIgnore(
                    'INSERT IGNORE INTO pages (slug, title, summary, body, status, sort_order)
                     VALUES (:slug, :title, :summary, :body, 1, :sort_order)'
                )
            );
            $stmt->execute($params);
            $inserted += $stmt->rowCount();
        }

        return $inserted;
    }

    /** 把 Markdown 里的相对 .md 链接改成站内地址，导入后不会变成死链 */
    /**
     * 相对链接按导入后的真实路径改写：链接是相对「源文件所在目录」的，
     * 导入后 slug 拍平到 <前缀>/<源目录>/<文件>，所以先把 ../ 收敛掉再拼前缀。
     */
    private static function rewriteLinks(string $markdown, string $prefix, string $rel = ''): string
    {
        $base = Plugin::basePath() . '/' . trim($prefix, '/') . '/';

        return (string)preg_replace_callback(
            '/\]\((?!https?:|\/|#|mailto:)([^)\s#]+?)\.md(#[^)\s]*)?\)/u',
            static function (array $m) use ($base, $rel): string {
                $dir = trim(str_replace('\\', '/', dirname($rel)), '/');
                $parts = ($dir === '' || $dir === '.') ? [] : explode('/', $dir);

                foreach (explode('/', $m[1]) as $segment) {
                    if ($segment === '..') {
                        array_pop($parts);
                    } elseif ($segment !== '' && $segment !== '.') {
                        $parts[] = $segment;
                    }
                }

                return '](' . $base . implode('/', $parts) . ($m[2] ?? '') . ')';
            },
            $markdown
        );
    }

    /** bin/nomen plugins pages:import <目录> [--prefix=local-docs] [--update] */
    public static function importCommand(): int
    {
        $argv = $_SERVER['argv'] ?? [];
        $args = [];
        foreach ($argv as $i => $value) {
            if ($value === 'pages:import') {
                $args = array_slice($argv, $i + 1);
                break;
            }
        }

        $dir = '';
        $prefix = 'local-docs';
        $update = false;
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--prefix=')) {
                $prefix = substr($arg, 9);
            } elseif ($arg === '--update') {
                $update = true;
            } elseif (!str_starts_with($arg, '-')) {
                $dir = $arg;
            }
        }

        if ($dir === '') {
            fwrite(STDERR, '用法：php bin/nomen plugins pages:import <目录> [--prefix=local-docs] [--update]' . PHP_EOL);
            return 1;
        }

        $count = self::import($dir, $prefix, $update);
        if ($count < 0) {
            fwrite(STDERR, '目录不存在：' . $dir . PHP_EOL);
            return 1;
        }

        echo '导入 ' . $count . ' 篇（前缀 ' . trim($prefix, '/') . '）' . PHP_EOL;

        return 0;
    }
}
