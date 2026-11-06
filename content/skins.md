---
title: 皮肤
slug: skins
summary: 换一套外观，或者只覆盖某个页面。
sort: 50
---

一个皮肤就是一个目录 `skins/<slug>/`，里面必须有 `skin.json`：

```json
{
    "name": "Nomen 2026",
    "version": "1.0.0",
    "description": "默认皮肤",
    "license": "GPL-3.0-only",
    "author": ["Yunyun"]
}
```

目录名就是 slug，写进 `config/skins.php` 的 `default`（或环境变量 `DEFAULT_SKIN`）就是站点默认皮肤。用户可以在设置页换自己的，登录后写进 `users.skin`。

## 目录

| 路径 | 作用 |
|---|---|
| `layouts/` | 页面外壳（`default` / `app` / `admin`），提供哪个就覆盖哪个 |
| `templates/` | 页面模板覆盖，和核心 `templates/` 同名就顶掉 |
| `partials/` | 皮肤自己的片段，布局里按相对路径 require |
| `css/`、`js/` | 皮肤自己的静态资源（放 `public/assets/skins/<slug>/` 也行） |

## 模板覆盖

`Response::view('landing')` 的查找顺序：

1. 当前皮肤的 `templates/landing.php`
2. 默认皮肤的 `templates/landing.php`
3. 核心的 `templates/landing.php`

想改首页、登录页、注册页，直接把同名文件放进皮肤目录，不用动核心。公共片段用 `Skins::template('_site_sky')` 取，皮肤也能覆盖。

## 插件页面

插件页面用 `@<slug>/<name>` 渲染，皮肤可以覆盖：

1. 当前皮肤的 `templates/@<slug>/<name>.php`
2. 默认皮肤的 `templates/@<slug>/<name>.php`
3. 插件的 `templates/<name>.php`

插件自带的布局叫 `@<slug>/<layout>`，皮肤同样可以在 `layouts/@<slug>/<layout>.php` 覆盖。插件资源走 `/plugin-assets/<slug>/<path>`，不用复制进皮肤。

## 后台菜单

后台左边菜单由 `Admin::menuItems()` 统一渲染，核心和插件注册的项按 `order` 排序。皮肤的后台布局不要写死菜单。

## 品牌变量

页面上能看到的东西都从 `config/brand.php` 取，别在布局里写死：

| 键 | 说明 |
|---|---|
| `name` | 站点名，标题、左上角、页脚都用它 |
| `logo` | 站点图标，左上角、favicon 都用它 |
| `base_url` | 绝对地址，canonical 和示例代码用它 |
| `copyright` | 页脚版权行，留空就不显示 |
| `footer_links` | 页脚链接数组，每项 `['label' => ..., 'url' => ...]`；Pages 启用时会自动补「文档」 |
| `powered_by` | 页脚署名，`false` 关掉；关掉后首页要自己补齐署名和版本页入口（见「页脚署名」） |

## 页脚署名

默认页脚有一行 `Powered by Nomen`，链到 `/special/version`。这是 GPLv3 第 7 条 (b) 款的要求，拆开是两条独立的：

- 首页上看得到 `Powered by Nomen`；
- 首页上有一个能点开的链接，落到 `/special/version`。

两条可以合成一个元素，也可以分开放。`config/brand.php` 里把 `powered_by` 改成 `false` 就不渲染默认那条，程序层面不拦；但上面两条还在，得自己在首页补上（见 `NOTICE`）。

## 注意

- 布局和模板都是直接 `require` 的 PHP，皮肤代码等于站点代码权限，只装信得过的。
- 应用的路由、POST 逻辑、CSRF 在控制器里，皮肤只决定长什么样。
- 改完不用清缓存，下一页就生效；改了 CSS / JS 记得换 `?v=`，值用 8 位随机字符。
- 皮肤目录里带一份 `LICENSE`，`skin.json` 的 `license` 写 SPDX 标识（如 `GPL-3.0-only`），`Special:Version` 的协议名会链到 `/special/version/skin/<slug>/license`。
