# Pages

在 Nomen 里写文档页面。管理员直接在站内新建、编辑、发布，不需要另建文档站。

Pages 是独立项目，不属于 Nomen 本体，按 GPLv3 单独发布：
<https://github.com/NomenID/plugin-pages>。

## 安装

1. 把 `pages/` 放进 `plugins/`。
2. 在 `config/plugins.php` 的 `enabled` 里加 `'pages'`。
3. 复制一份配置到 `config/plugins/pages.json`，改挂载点：

```json
{
    "base_path": "/w"
}
```

装完打开 `/w`。第一次访问会自动建 `pages` 表，并把 `content/` 里的初始文档导进去（只有「欢迎」和「沙盒」两页，完整文档维护在 Nomen 官方站）；表已经存在就不会再导。后来新增的文档用 `php bin/nomen plugins pages:sync` 补，只补缺，不覆盖已经在站内改过的页面。

## 谁能编辑

只有管理员。编辑入口在文档页右上角的「编辑」，新建入口在 `/w` 列表页。草稿只有管理员看得到。

## 地址

`base_path` 可以改成 `/docs`、`/wiki` 之类。改完直接生效，不用清缓存；如果页脚有指向文档的链接（`config/brand.php` 的 `footer_links`），记得一起改。页面自己的地址是 `base_path + '/' + slug`，slug 只能用小写字母、数字和连字符。

## Markdown

支持标题、段落、列表、引用、代码块、表格、链接、图片、粗体、斜体和删除线。HTML 会被转义，链接只放行 http(s)、mailto 和站内相对地址。

## 多语言

页面正文只有一份，不按语言分开。界面上的固定文案走 `t()`，缺翻译时显示中文。

## 数据

表名 `pages`，插件自己建，不碰核心表。字段：

| 字段 | 说明 |
|---|---|
| `slug` | 地址，唯一 |
| `title` / `summary` / `body` | 标题、列表摘要、Markdown 正文 |
| `status` | 1 发布，0 草稿 |
| `sort_order` | 列表和侧栏排序，小的在前 |
| `updated_by` | 最后修改的管理员 |

## 后台

插件在后台「内容」分组注册 `/admin/content`，三个选项卡：全部、已发布、草稿。列表里可以直接编辑、查看和删除；编辑器支持改地址、摘要、Markdown 正文、发布状态和排序。

面板是服务端 HTML（外层是 Nomen 的 Vue 后台），表单带 `data-admin-ajax`，保存后回到列表。其它插件可以挂这些钩子：

| 钩子 | 类型 | 参数 | 时机 |
|---|---|---|---|
| `pages.content.before_save` | filter | `data`, `existing`（新建时为 null）, `isNew` | 写库前，可改字段 |
| `pages.content.saved` | run | `pageId`, `data`, `isNew`, `userId` | 保存后 |
| `pages.content.deleted` | run | `pageId`, `page`, `userId` | 删除前 |
| `pages.admin.editor.extras` | filter | `html`, `page` | 编辑器底部，可加自定义字段说明 |

## 许可

GPL-3.0-only，协议全文见 [LICENSE](LICENSE)。

Copyright (C) 2025-2026 Yunyun
