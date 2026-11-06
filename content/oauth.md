---
title: 应用接入
slug: oauth
summary: OAuth 2.0 授权码流程、scope 和用户信息接口。
sort: 30
---

接入方只需要实现 OAuth 2.0 授权码流程。PKCE 只接受 `S256`，`plain` 会被拒绝。

## 建应用

两种方式：

- 管理后台 →「应用」→「新建应用」：站长自己建，不依赖开放平台插件。
- 开放平台 `/open`：开发者自助注册、建应用，需要启用 `open-platform` 插件。

填名称、说明、主页、回调地址（每行一个）和权限。客户端类型选机密或公共：公共客户端没有 Secret，强制 PKCE，适合桌面端、SPA 和 CLI。创建后 Client ID 和 Secret 只显示一次，先存好。

回调地址必须和请求里的 `redirect_uri` 逐字节一致，这是授权码唯一的安全边界。改完立即生效。

## 端点

| 端点 | 用途 |
|---|---|
| `/oauth/authorize` | 授权页，参数 `response_type=code`、`client_id`、`redirect_uri`、`scope`、`state` |
| `/oauth/token` | 用 code 换 token；刷新用 `grant_type=refresh_token` |
| `/api/userinfo` | 读取用户信息，请求头带 `Authorization: Bearer <token>` |
| `/api/token-info` | 校验 token 是否有效 |
| `/oauth/revoke` | 撤销 token |

## scope

- `openid`：必给，返回应用级 `sub`。
- `profile`：用户名、昵称、头像。
- `email`：已验证邮箱和验证状态。

只要身份就申请 `openid profile`。应用注册时登记的 scope 也是一道闸，超出登记范围的申请会被拒绝。

## 换 token

`/oauth/token` 收 `application/x-www-form-urlencoded`，也支持 Basic 认证。

```http
POST /oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code&code=...&redirect_uri=...&client_id=...&code_verifier=...
```

授权码 10 分钟有效，访问令牌 1 小时，刷新令牌 180 天。刷新一次旧 refresh_token 立刻失效，记得把新的存回去。

## 用户信息

```http
GET /api/userinfo
Authorization: Bearer <token>
```

返回 `sub`、`id`，带 `profile` 时还有 `username`、`display_name`、`avatar`，带 `email` 时还有 `email`、`email_verified`。

`sub` 是应用级 ID：同一个人在另一个应用里会拿到不同的值，别拿它跨应用关联用户。

## 注意

- `client_secret` 只放在服务端，不要进前端代码或仓库。
- `redirect_uri` 必须和开放平台里登记的地址逐字节一致，别依赖末尾斜杠的跳转。
- 回调地址可能带 `state` 以外的参数，接入方只校验自己发出去的 `state`，别把查询串整个丢掉。
