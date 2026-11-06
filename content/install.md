---
title: 安装与升级
slug: install
summary: 网页向导、命令行安装、nginx 伪静态和升级步骤。
sort: 20
---

Nomen 最后只需要一份配置文件：`config/installed.php`。里面有数据库密码和 pepper，不要提交、不要外传。

## 先确认 web root 是 public/

入口在 `public/index.php`，nginx / Apache 的 root 必须指到 `public/`。指到项目根的话，你会看到根目录那个 `index.html`，它在提醒你改这里。

## 伪静态

安装页第 1 步会自动检测。也可以直接访问 `/__rewrite_probe`：返回 `ok` 就说明伪静态没问题，返回 404 就是没配。

nginx：

```nginx
location / {
    try_files $uri $uri/ /index.php$is_args$args;
}
```

Apache 的 `public/.htaccess` 已经带了规则，确认 `mod_rewrite` 开了、站点允许 `AllowOverride All`。

## 网页向导

1. 把代码放到站点目录（发布 zip 解压即可，`vendor/` 和前端产物都在里面）。
2. 浏览器打开站点，没装过会自动跳到 `/nomen-install/`。
3. 五步：环境检查 → 数据库 → 站点与管理员 → 扩展 → 安装。
4. 扩展这一步默认勾 `open-platform` 和 `pages`，`geoip` 按需勾。装完还能改 `config/plugins.php`。
5. 装完把 `public/nomen-install/` 删掉或改名——留着等于把安装页开放给所有人。
6. 打开站点，用刚建的管理员登录。

## 命令行

```bash
php bin/nomen install
```

会依次问数据库、站点信息、要启用的扩展、管理员账号，然后建库、导表、建管理员、写 `config/installed.php` 和 `config/plugins.php`。已经装过会拒绝，确实要重装加 `--force`。

## 数据库

安装器会尽量自己建库，但面板建的数据库账号通常没有建库权限，先建好更省事：

```sql
CREATE DATABASE `nomen` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nomen'@'localhost' IDENTIFIED BY '换成随机密码';
GRANT ALL PRIVILEGES ON `nomen`.* TO 'nomen'@'localhost';
FLUSH PRIVILEGES;
```

表结构不用手动导，安装器会跑 `database/schema.sql`（21 张表）。

## 前端

发布 zip 里已经带好 `public/assets/dist/`，跳过这步。从源码部署才需要：

```bash
cd frontend
npm install
npm run build
```

## 邮件

验证码、恢复链接、通知都走 SMTP。装完在 `config/installed.php` 的 `smtp` 段里填，或者用 `.env` 覆盖。

通知邮件是排队发的（`notifications.email_sent_at`），要有个常驻进程消费：

```bash
php scripts/mail-drain.php
```

用 systemd 或 cron 拉起，同时只能有一个实例（脚本自己用 `scripts/mail-drain.lock` 加锁）。

## 插件与后台

- `php bin/nomen plugins list`：看插件状态和插件注册的命令。
- `php bin/nomen plugins migrate`：跑插件自己的 `database/migrations`。
- `php bin/nomen plugins pages:sync`：把 Pages 插件 `content/` 里新增的文档补进库，不覆盖已经改过的页面。

管理后台的「应用」页不依赖开放平台，可以直接建 OAuth 应用；「接入点」页列出协议端点和插件挂载点，Pages 的 `/w`、开放平台的 `/open` 都在那里，能改的路径可以直接改。

## 升级

```bash
git pull
composer install --no-dev
php bin/nomen migrate
php bin/nomen plugins migrate
```

`migrate` 只跑 `database/migrations/` 下没跑过的 SQL，记录在 `schema_migrations` 表；`plugins migrate` 跑各插件自己的迁移，记录在 `plugin_migrations` 表。

## 排错

| 现象 | 原因 |
|---|---|
| 首页只有一句「服务器内部错误，请稍后重试」 | 数据库连不上：看 `config/installed.php` 的 `db`，或者站点错误日志 |
| 首页能开，其它路径全 404 | 伪静态没配：`/__rewrite_probe` 返回 404 就是它 |
| 页面能开但没样式 | `public/assets/dist/` 没构建 |
| 一直跳安装页 | `config/installed.php` 不在，或 PHP 读不到它 |
| 收不到验证码 | SMTP 没填，或 `mail-drain.php` 没跑 |
