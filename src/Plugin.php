<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE file in this repository for the full text.
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
        Plugins::route('GET', $base . '/assets/pages-js', [Controller::class, 'js']);
        Plugins::route('GET', $base . '/{slug:.*}/edit', [Controller::class, 'edit'], ['auth', 'admin']);
        Plugins::route('POST', $base . '/{slug:.*}/edit', [Controller::class, 'update'], ['auth', 'admin']);
        Plugins::route('POST', $base . '/{slug:.*}/delete', [Controller::class, 'destroy'], ['auth', 'admin']);
        // 侧边栏保存：slug 以 - 开头，和普通页面地址不会撞
        Plugins::route('POST', $base . '/-/nav', [Controller::class, 'saveNav'], ['auth', 'admin']);
        // 页面地址支持层级（local-docs/install），目录地址回退到 index / 第一条
        Plugins::route('GET', $base . '/{slug:.*}', [Controller::class, 'show']);

        Plugins::command('pages:sync', [Store::class, 'sync'], '把 content/ 里还没进库的文档页导入');
        Plugins::command('pages:import', [Store::class, 'importCommand'], '从目录导入 Markdown：pages:import <目录> [--prefix=local-docs]');

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
