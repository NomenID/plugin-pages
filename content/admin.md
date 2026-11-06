---
title: 管理后台
slug: admin
summary: 后台有哪些页面，插件怎么加自己的页面、菜单和接入点。
sort: 44
---

管理后台的地址是 `/admin`，只有 `role = admin` 的账号能进。后台文案保持中文，不跟站点语言切换。

## 核心页面

| 页面 | 地址 | 做什么 |
|---|---|---|
| 概览 | `/admin` | 用户数、恢复工单、应用、Redis、最近活动 |
| 用户 | `/admin/users` | 搜索、查看详情、启用/禁用、授予/撤销管理员 |
| 应用 | `/admin/apps` | 建 OAuth 应用、改回调地址和权限、重置密钥、停用、删除 |
| 接入点 | `/admin/mounts` | 看协议端点和插件挂载点，能改的路径直接改 |
| 恢复工单 | `/admin/recovery` | 审核账号恢复申诉 |
| 活动日志 | `/admin/logs` | 审查全站操作，按类型、执行者、目标筛选 |

应用管理不依赖开放平台插件。停用 `open-platform` 以后，管理员仍然可以在「应用」页建应用、拿 Client ID 和 Secret。

## 活动日志

`/admin/logs` 按时间倒序列出全站操作，默认每页 50 条。

- 执行者可以填用户名或 IP，精确匹配；目标可以填页面地址、用户名、应用名或 ID。
- 日志类型按功能模块分组：登录与账户、个人资料、安全设置、设备、应用授权、开放平台、账号恢复、家庭、管理员操作、页面。
- 每条记录带来源 IP、设备和 User-Agent。点时间或「详情」进 `/admin/logs/<id>` 看完整信息。
- 有字段变化时记录对照：短文本标出改动的字，头像显示新旧两张图，简介和页面正文按行对照。
- 「导出 CSV」按当前筛选条件导出，最多 5 万条，Excel 打开不会乱码。

日志覆盖认证、账户设置、设备、OAuth、恢复、家庭、管理员操作，以及 Pages 插件的页面创建、编辑、删除。日志只读，后台没有删除或修改入口。

## 建一个应用

1. 后台「应用」→「新建应用」。
2. 填名称、说明、主页、回调地址（每行一个）、权限。
3. 选客户端类型：机密客户端有 Secret，适合服务端；公共客户端没有 Secret，强制 PKCE，适合桌面端、SPA、CLI。
4. 创建后 Client ID 和 Secret 只显示一次，先存好。
5. 详情页会列出授权、令牌、用户信息、撤销四个端点，以及授权链接和换令牌的示例。

回调地址是授权码唯一的安全边界，必须和请求里的 `redirect_uri` 完全一致；改完立即生效。

## 接入点

「接入点」页分两块：

- 协议端点：`/oauth/authorize`、`/oauth/token`、`/api/userinfo`、`/api/token-info`、`/oauth/revoke`，固定不变。
- 插件挂载点：插件在 `plugin.json` 的 `mounts` 里声明的公开入口。Pages 的 `/w`、开放平台的 `/open` 都在这里。声明了 `config` 的挂载点可以直接改路径，改完写回 `config/plugins/<slug>.json`，下一次请求生效。

## 插件加后台页面

清单里写：

```json
"adminPages": {
    "example": {
        "title": "示例",
        "description": "插件自己的后台页面",
        "handler": ["Nomen\\Plugin\\Example\\AdminPage", "render"],
        "menu": "示例",
        "order": 35
    }
}
```

或者在 `boot` 里：

```php
\Nomen\Src\Admin::page('example', [
    'title' => '示例',
    'handler' => [AdminPage::class, 'render'],
    'menu' => '示例',
    'order' => 35,
]);
```

处理函数返回 HTML 字符串，核心用后台布局套起来。页面地址是 `/admin/example`，菜单由 `Admin::menuItems()` 统一排序；`menu` 写 `false` 就不进菜单，写字符串就用这个文字。`order` 越小越靠前。

需要加样式或脚本：

```php
\Nomen\Src\Admin::style(\Nomen\Src\Plugins::assetUrl('example', 'admin.css'));
\Nomen\Src\Admin::script(\Nomen\Src\Plugins::assetUrl('example', 'admin.js'));
```

处理函数里可以再调一次 `Admin::requireAdmin()` 拿当前管理员；路由上的 `admin` 中间件已经挡过一道，这一步是兜底。
