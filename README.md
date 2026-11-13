# Pages

在 Nomen 里写文档页面。管理员直接在站内新建、编辑、发布，不需要另建文档站。

Pages 是独立项目，不属于 Nomen 本体，按 Apache-2.0 单独发布：
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

**slug 可以带层级**：`local-docs/install` 就是 `/w/local-docs/install`。访问目录地址（`/w/local-docs`）时，
先找该目录的 `index` 页；没有就按侧边栏菜单的顺序跳到第一条，再没有就按排序取第一条子页面。

## 侧边栏

侧边栏按目录配置，一个目录一份，后台「内容 → 目录」里编辑。每个目录可以选两种方式：

- **自动生成**：按 slug 树和页面排序拼出来，新页面不用回来改；子目录成为一组，只有当前页所在的那条链展开。
- **手动写条目**：一行一个条目，格式如下。

```
概览 | local-docs/index
  安装 | local-docs/install
  升级 | local-docs/upgrade
  内部草稿 | local-docs/draft | hide
```

手动方式下缩进两个空格表示下一级；第三列写 `hide` 就不在公开页面显示；目标可以写页面 slug，也可以写完整网址。
自动方式下子目录的分组标题默认取该目录 `index` 页的标题，目录记录里填了标题就用填的。

每个目录还能单独设标题、摘要和排序，这几个值也会用在 `/w` 首页的目录条目上。
**没启用侧边栏的目录不显示边栏**，正文自动占满。页面从自己所在目录往上找最近一份启用的目录记录，
所以根目录配一次就能盖住全站。自动模式里子目录要自己出现在上级边栏里，需要上级目录也有记录（根目录那条就行）。

## 从目录导入

```
php bin/nomen plugins pages:import docs --prefix=local-docs
```

把目录里的 Markdown 按相对路径导成页面（`docs/install.md` → `local-docs/install`，目录根的 `README.md`
→ `local-docs/index`），只补缺、不覆盖库里改过的页面。加 `--update` 才会覆盖已有的同名页面
（用来把站内副本跟 `docs/` 同步）。适合把发布包里的 `docs/` 挂成站内文档。

## Markdown

支持标题、段落、列表、引用、代码块、表格、链接、图片、粗体、斜体和删除线。HTML 会被转义，链接只放行 http(s)、mailto 和站内相对地址。

## 多语言

页面正文只有一份，不按语言分开。界面上的固定文案走 `t()`，缺翻译时显示中文。

## 数据

表名 `pages`，插件自己建，不碰核心表。字段：

| 字段 | 说明 |
|---|---|
| `slug` | 地址，唯一；可以带 `/` 表示层级 |
| `title` / `summary` / `body` | 标题、列表摘要、Markdown 正文 |
| `status` | 1 发布，0 草稿 |
| `sort_order` | 列表和侧栏排序，小的在前 |
| `updated_by` | 最后修改的管理员 |

`page_menus` 存目录记录，一个目录一行：`path`（目录，空串是根）、`title` / `summary` / `sort_order`、
`mode`（`auto` 或 `manual`）、`items`（手动条目 JSON）、`enabled`（这个目录显示侧边栏）。
老版本装过的站会在第一次访问时自动补上后加的列，不用手动迁移。

## 后台

插件在后台「内容」分组注册 `/admin/content`，四个选项卡：全部、已发布、草稿、目录。列表里可以直接编辑、查看和删除；编辑器支持改地址、摘要、Markdown 正文、发布状态和排序。目录选项卡里配目录标题、摘要、排序和侧边栏方式。

面板是服务端 HTML（外层是 Nomen 的 Vue 后台），表单带 `data-admin-ajax`，保存后回到列表。其它插件可以挂这些钩子：

| 钩子 | 类型 | 参数 | 时机 |
|---|---|---|---|
| `pages.content.before_save` | filter | `data`, `existing`（新建时为 null）, `isNew` | 写库前，可改字段 |
| `pages.content.saved` | run | `pageId`, `data`, `isNew`, `userId` | 保存后 |
| `pages.content.deleted` | run | `pageId`, `page`, `userId` | 删除前 |
| `pages.admin.editor.extras` | filter | `html`, `page` | 编辑器底部，可加自定义字段说明 |

## 许可

Apache-2.0，协议全文见 [LICENSE](LICENSE)。

Copyright (C) 2025-2026 Yunyun (Wang Yaoze)
