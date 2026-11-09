<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the GNU General Public License, version 3 only, with
 * additional attribution terms in the NOTICE file.
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
        $value = preg_replace('/[^a-z0-9-]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return substr($value, 0, 100);
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
}
