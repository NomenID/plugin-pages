---
title: 概览
slug: start
summary: Nomen 是什么，文档从哪里看起。
sort: 10
---

Nomen 是自托管的账号中心：一台服务器跑一份，几个站点共用一套账号，通过 OAuth 2.0 接入。登录、二步验证、找回密码和账号设置都在站内完成。

官方站点 https://nomen.caellab.com/ 就是用它自己跑的，这份文档也写在 Pages 插件里。

## 从哪里看起

- [安装与升级](install)：把 Nomen 跑起来，或者升级到新版本。
- [应用接入](oauth)：让别的站点用 Nomen 登录。
- [开放平台](open-platform)：开发者注册、自助建应用。
- [管理后台](admin)：用户、应用、接入点和恢复工单。
- [插件](plugins)：写一个插件，给 Nomen 加功能。
- [钩子](hooks)：核心会发哪些事件。
- [模板](templates)：改页面、覆盖布局、插件模板。
- [皮肤](skins)：换一套外观。
- [源码与许可](source)：代码在哪、怎么许可。

## 环境

PHP 8.1+、MySQL 8、Composer；Redis 可选，没有会降级。改前端还需要 Node 18+。
