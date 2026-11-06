---
title: 插件
slug: plugins
summary: 插件清单、配置、路由、后台页面、挂载点、迁移和命令。
sort: 40
---

一个插件是一个目录 `plugins/<slug>/`，里面必须有 `plugin.json`。装插件不用改核心代码：放进 `plugins/`，再写进 `config/plugins.php` 的 `enabled` 列表。

```json
{
    "name": "Open Platform",
    "version": "0.1.0",
    "description": "开发者门户",
    "license": "GPL-3.0-only",
    "author": ["Yunyun"],
    "requires": {
        "Nomen": ">=0.1",
        "php": ["gd", "fileinfo"]
    },
    "autoload": { "Nomen\\Plugin\\OpenPlatform\\": "src/" },
    "boot": ["Nomen\\Plugin\\OpenPlatform\\Plugin", "boot"],
    "hooks": {
        "user.login.succeeded": [["Nomen\\Plugin\\Example\\Hooks", "onLogin"]]
    },
    "specialPages": {
        "example": {
            "title": "示例状态",
            "access": "admin",
            "handler": ["Nomen\\Plugin\\Example\\SpecialExample", "render"]
        }
    },
    "adminPages": {
        "example": {
            "title": "示例",
            "description": "插件自己的后台页面",
            "handler": ["Nomen\\Plugin\\Example\\AdminPage", "render"],
            "menu": "示例",
            "order": 35
        }
    },
    "mounts": {
        "portal": {
            "path": "/example",
            "title": "示例入口",
            "access": "public",
            "config": "base_path"
        }
    }
}
```

| 字段 | 说明 |
|---|---|
| `name` / `version` / `description` / `license` / `author` | 展示和排查用，`Special:Version` 会列出来 |
| `requires.Nomen` | 版本约束，比如 `">=0.1"`；不满足就跳过 |
| `requires.php` | 需要的 PHP 扩展；缺一个就跳过 |
| `autoload` | PSR-4 前缀 → 目录，相对插件根 |
| `boot` | 加载时调一次的回调，用来注册路由、后台页面、命令 |
| `hooks` | 事件名 → 回调；可以写一个，也可以写一组，带 `priority` 的对象形式 |
| `specialPages` | `/special/<slug>` 页面；`access` 是 `public` 或 `admin` |
| `adminPages` | `/admin/<slug>` 页面；`menu` 为 `false` 时不进菜单 |
| `mounts` | 公开挂载点；`config` 指向插件配置里的路径键 |

## 配置

插件配置放 `config/plugins/<slug>.json`（也支持 `.ini`），不要写进插件目录。升级插件不会覆盖站长的设置。

```json
{
    "base_path": "/w"
}
```

```php
$config = \Nomen\Src\Plugins::config('pages', ['base_path' => '/w']);
```

读取时合并到默认值上；文件不存在就用默认值。后台改挂载点这类要写回：

```php
\Nomen\Src\Plugins::setConfig('pages', ['base_path' => '/docs']);
```

## 路由

在 `boot` 里注册，路径通常来自配置：

```php
public static function boot(): void
{
    $base = self::basePath();

    Plugins::route('GET', $base, [Controller::class, 'index']);
    Plugins::route('GET', $base . '/{slug}', [Controller::class, 'show']);
    Plugins::route('POST', $base . '/{slug}/edit', [Controller::class, 'update'], ['auth', 'admin']);
    Plugins::route('GET', $base . '/assets/{path:.*}', [Controller::class, 'asset']);
}
```

- `{name}` 匹配一段，`{path:.*}` 匹配多段。
- 第三个参数是中间件：`auth` 要求登录，`admin` 要求管理员。
- 插件路由在核心路由之前灌进去，同名的精确路径会被核心覆盖，插件顶不掉 `/login`、`/oauth/token` 这些入口。
- 未验证邮箱和恢复模式这两个全局闸在路由之前执行，插件页面不在它们的白名单里。插件自己的 POST 接口在恢复模式下仍然可达，需要的话自己检查。

## 后台页面

`Admin::page()` 或清单里的 `adminPages` 注册，地址是 `/admin/<slug>`。处理函数返回 HTML 字符串，核心用后台布局套起来。`Admin::style()` / `Admin::script()` 可以加资源，菜单由注册表统一渲染。

详细的字段、排序和权限说明见 [管理后台](admin)。

## 挂载点

挂载点是公开入口的声明，不是路由本身。后台「接入点」页会列出来，声明了 `config` 的可以直接改路径，写回 `config/plugins/<slug>.json`。

```php
$base = \Nomen\Src\Plugins::mountUrl('pages'); // '/w'，没装返回 null
```

真正的路由还是用 `Plugins::route()` 注册；路径变了要用 `Plugins::config()` 重新算，别写死。

## 模板与静态资源

插件模板放 `plugins/<slug>/templates/`，用 `@<slug>/<name>` 渲染：

```php
Response::view('@open-platform/landing', $data, 'default');
```

皮肤可以在 `skins/<skin>/templates/@<slug>/<name>.php` 覆盖。插件也可以自带布局：`layouts/<name>.php`，第三参数写 `'@<slug>/<layout>'`。静态资源放 `assets/`，用 `Plugins::style()` / `Plugins::script()` 登记，地址是 `/plugin-assets/<slug>/<path>`。

详细规则见 [模板](templates)。

## 迁移

插件自己的表放 `plugins/<slug>/database/migrations/`，文件名带 `.mysql.sql` / `.sqlite.sql` 区分方言：

```bash
php bin/nomen plugins migrate
php bin/nomen plugins migrate example
```

跑过的文件记在 `plugin_migrations` 表。只往前加，已经发布的文件不要改。插件不要 `ALTER` 核心表，要加字段就用自己的表。

## CLI 命令

```php
Plugins::command('example:sync', [Sync::class, 'run'], '把示例数据补进库');
```

```bash
php bin/nomen plugins list
php bin/nomen plugins example:sync
```

处理函数返回字符串会打印出来，返回整数会打印「完成（N）」。

## 钩子

```php
Hooks::add('user.login.succeeded', [Hooks::class, 'onLogin'], 10); // 数字越小越先跑
Hooks::run('user.login.succeeded', [$userId, $username]);
$claims = Hooks::filter('userinfo.claims', $claims, $user, $scopes);
```

完整事件表、参数和注意事项见 [钩子](hooks)。

## 特殊页面

`/special/<slug>`，处理函数返回 HTML 字符串，外壳由 `templates/special.php` 套。核心自带 `/special/version` 和 `/special/plugins`。后台页面用 `adminPages`，别用特殊页面硬凑。

## 注意

- 插件跑的是全权限 PHP，只装信得过的。
- 插件不要 `ALTER` 核心表；核心表结构不是插件 API。
- 插件依赖用 Composer 装到项目根，别塞一份 `vendor/` 进插件目录。
- 插件目录带一份 `LICENSE`：通知块、空行、协议全文；`plugin.json` 的 `license` 写 SPDX 标识。
- 每个插件带一份 `README.md`，写清路由、后台页面、挂载点、配置、迁移和命令。
