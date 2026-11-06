---
title: 钩子
slug: hooks
summary: Nomen 会发哪些事件、参数是什么、插件回调怎么写。
sort: 45
---

钩子用来在核心动作完成后通知插件，或者让插件改一份已经组装好的数据。它不负责拦截登录、改密码、审核恢复这些安全判断。

## 写一个回调

插件清单里直接挂：

```json
"hooks": {
    "user.login.succeeded": [["Nomen\\Plugin\\Example\\Hooks", "onLogin"]],
    "userinfo.claims": [
        {"handler": ["Nomen\\Plugin\\Example\\Hooks", "addClaims"], "priority": 5}
    ]
}
```

也可以在 `boot` 里写：

```php
\Nomen\Src\Hooks::add('user.login.succeeded', [Hooks::class, 'onLogin'], 10);
```

`priority` 数字越小越先跑，默认 10。同一个事件里优先级相同的按注册顺序执行。

## 两种钩子

```php
// 通知：按顺序调用，回调返回 false 会停掉后面的回调
Hooks::run('user.login.succeeded', [$userId, $username]);

// 取值：回调拿到当前值，返回非 null 就替换它
$claims = Hooks::filter('userinfo.claims', $claims, $user, $scopes);
```

`run` 的第三个参数是 `abortable`，默认 `true`。写成 `false` 时，回调返回 `false` 会直接抛异常；核心目前没有用它做安全拦截，插件也不要依赖这个行为。

## 核心事件

| 事件 | 类型 | 参数 | 时机 |
|---|---|---|---|
| `user.registered` | run | `userId`, `username`, `email`（可能为 null） | 注册成功、邮箱记录同步之后 |
| `user.login.succeeded` | run | `userId`, `username` | 会话建立之后 |
| `user.logout` | run | `userId`, `username` | 清会话之前 |
| `userinfo.claims` | filter | `claims`, `user`, `scopes` | `/api/userinfo` 组装完 claims 之后 |
| `oauth.authorize.approved` | run | `userId`, `clientId`, `scope`, `quickLogin` | 用户同意授权、写入授权记录之后 |
| `oauth.token.issued` | run | `userId`, `clientId`, `scopes`, `grantType` | 发出 access token 之后 |
| `oauth.token.revoked` | run | `tokenTypeHint` | 撤销端点处理完 |
| `admin.user.status_changed` | run | `userId`, `status`, `adminId` | 管理员启用/禁用账户之后 |
| `admin.user.role_changed` | run | `userId`, `role`, `adminId` | 管理员授予/撤销管理员之后 |
| `email.sending` | filter | `payload`（`to` / `subject` / `body`）, `embedded` | 真正投递 SMTP 之前；返回数组可替换 |
| `notification.created` | run | `userId`, `category`, `title`, `body`, `notificationId` | 站内通知入库之后、发邮件之前 |
| `viewer.props` | filter | `viewer`, `userId`, `user` | SPA 的 viewer 数据组装完 |

## 注意

- 钩子跑在请求里，回调抛异常会把当前请求打成 500；自己包好 try/catch。
- `userinfo.claims` 能改 `sub`、`username`、`email` 这些字段，插件要清楚自己在改什么。
- 不要把明文密码、Token、密钥写进日志或通知。
- 需要给页面加导航，用 `Nav::add()`，不要靠改皮布局；需要加后台页面，用 `Admin::page()`。
