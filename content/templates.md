---
title: 模板
slug: templates
summary: 页面模板怎么覆盖，布局和插件模板怎么解析。
sort: 46
---

Nomen 的页面是服务端 PHP 模板，不是每个页面都有一份前端工程。改页面优先覆盖模板，别改核心文件。

## 核心模板

核心模板在 `templates/`，控制器用 `Response::view()` 渲染：

```php
Response::view('login', ['error' => $error]);
```

数据数组会被 `extract()` 成变量，模板里直接用 `$error`。布局用第三个参数，默认 `default`：

```php
Response::view('admin/apps', $data, 'admin');
```

布局文件里拿到的 `$content` 是页面正文，`$title` 是标题。

## 皮肤覆盖

`Response::view('login')` 的查找顺序：

1. 当前皮肤的 `skins/<active>/templates/login.php`
2. 默认皮肤的 `skins/<default>/templates/login.php`
3. 核心的 `templates/login.php`

改首页、登录页、注册页，直接把同名文件放进皮肤目录，不用动核心。公共片段用 `Skins::template('_site_sky')` 取，皮肤也能覆盖。

## 插件模板

插件页面用 `@<slug>/<name>`：

```php
Response::view('@open-platform/landing', $data, 'default');
```

查找顺序：

1. 当前皮肤的 `templates/@open-platform/landing.php`
2. 默认皮肤的 `templates/@open-platform/landing.php`
3. 插件的 `plugins/open-platform/templates/landing.php`

插件自带布局时，布局参数写 `'@<slug>/<layout>'`，皮肤可以在 `layouts/@<slug>/<layout>.php` 覆盖。

## 布局

现有布局：

- `default`：站点页面，带头部、导航、页脚和移动端抽屉。
- `app`：SPA 外壳，只输出挂载点和 props JSON，导航由 Vue 渲染。
- `admin`：管理后台，带侧边栏和后台资源。

布局也是皮肤的一部分。皮肤没提供某个布局时，会退回默认皮肤的；再没有就报 500。

## 静态资源

- 核心和皮肤的 CSS/JS 在 `public/css/`、`public/js/`、`public/assets/`。
- 插件资源放 `plugins/<slug>/assets/`，用 `Plugins::assetUrl()` / `Plugins::style()` / `Plugins::script()` 引用，地址是 `/plugin-assets/<slug>/<path>`。
- 改 CSS / JS 后换 `?v=`，值用 8 位随机字符，别带日期或部署时间。

## 改模板时注意

- 公开模板和静态资源等于对外发布，注释只留「这块是干什么的」，别写内部路径、行号、构建命令。
- 模板里不要写死站点名、logo、域名，从 `Brand::get()` 取。
- 改完跑 `php -l <file>`；改了多语言文案跑 `php docs-internal/i18n/extract.php --check`。
- 页面里的表单要带 `Csrf::field()`，POST 处理在控制器里做，模板不碰权限判断。
