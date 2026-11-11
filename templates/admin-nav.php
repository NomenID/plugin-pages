<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE file in this repository for the full text.
 * Source: https://github.com/NomenID/plugin-pages
 * Project: https://nomenid.org/w/plugin-pages
 */

/**
 * @var array $sections
 * @var string $section
 * @var string $text
 * @var bool $enabled
 * @var string $base
 * @var string $csrf
 * @var array $pages
 * @var string $notice
 */
$label = static fn(string $path): string => $path === '' ? '根目录（/w）' : $path;
?>
<?php if ($notice !== ''): ?>
    <div class="a-alert a-alert--success"><?= htmlspecialchars($notice) ?></div>
<?php endif; ?>

<div class="a-row" style="gap:8px;flex-wrap:wrap;margin-bottom:16px">
    <?php foreach ($sections as $path): ?>
        <a class="a-btn a-btn--<?= $path === $section ? 'primary' : 'ghost' ?> a-btn--sm"
           href="/admin/content?tab=nav&amp;section=<?= urlencode($path) ?>"><?= htmlspecialchars($label($path)) ?></a>
    <?php endforeach; ?>
</div>

<form method="post" action="<?= htmlspecialchars($base) ?>/-/nav">
    <?= $csrf ?>
    <input type="hidden" name="section" value="<?= htmlspecialchars($section) ?>">

    <div class="a-field">
        <label class="a-label" for="nav_items">侧边栏条目</label>
        <textarea id="nav_items" class="a-input a-textarea a-mono" name="items" rows="14"
                  placeholder="概览 | local-docs/index&#10;  安装 | local-docs/install&#10;  升级 | local-docs/upgrade"><?= htmlspecialchars($text) ?></textarea>
        <p class="a-hint">
            一行一个条目：<code>标题 | 目标</code>。缩进两个空格表示下一级，第三列写 <code>hide</code> 就不在公开页面显示。
            目标写页面 slug（如 <code>local-docs/install</code>），也可以写完整网址。
            这一栏留空并且关掉开关，这个目录就不显示侧边栏。
        </p>
    </div>

    <div class="a-field">
        <label class="a-check">
            <input type="checkbox" name="enabled" value="1" <?= $enabled ? 'checked' : '' ?>>
            <span>在这个目录显示侧边栏</span>
        </label>
    </div>

    <div class="a-actions">
        <button class="a-btn a-btn--primary" type="submit">保存</button>
    </div>
</form>

<div class="a-field" style="margin-top:24px">
    <span class="a-label">可用的页面 slug</span>
    <p class="a-hint">
        <?php foreach ($pages as $page): ?>
            <code><?= htmlspecialchars((string)$page['slug']) ?></code>
        <?php endforeach; ?>
    </p>
</div>
