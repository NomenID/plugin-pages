<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the GNU General Public License, version 3 only, with
 * additional attribution terms in the NOTICE file.
 * Source: https://github.com/NomenID/plugin-pages
 * Project: https://nomenid.org/w/plugin-pages
 */

namespace Nomen\Plugin\Pages;

use Nomen\Src\Plugins;

class Plugin
{
    public static function boot(): void
    {
        $base = self::basePath();

        Plugins::route('GET', $base, [Controller::class, 'index']);
        Plugins::route('GET', $base . '/new', [Controller::class, 'create'], ['auth', 'admin']);
        Plugins::route('POST', $base . '/new', [Controller::class, 'store'], ['auth', 'admin']);
        Plugins::route('GET', $base . '/sitemap', [Controller::class, 'sitemap']);
        Plugins::route('GET', $base . '/assets/pages', [Controller::class, 'css']);
        Plugins::route('GET', $base . '/{slug}/edit', [Controller::class, 'edit'], ['auth', 'admin']);
        Plugins::route('POST', $base . '/{slug}/edit', [Controller::class, 'update'], ['auth', 'admin']);
        Plugins::route('POST', $base . '/{slug}/delete', [Controller::class, 'destroy'], ['auth', 'admin']);
        Plugins::route('GET', $base . '/{slug}', [Controller::class, 'show']);

        Plugins::command('pages:sync', [Store::class, 'sync'], '把 content/ 里还没进库的文档页导入');

        Api::boot();
        Admin::register();
    }

    /**
     * 文档挂载点。站长在 config/plugins/pages.json 里改，插件目录不用动。
     */
    public static function basePath(): string
    {
        $config = Plugins::config('pages', ['base_path' => '/w']);
        $base = trim((string)($config['base_path'] ?? '/w'));

        if ($base === '' || $base === '/') {
            $base = '/w';
        }

        return '/' . trim($base, '/');
    }
}
