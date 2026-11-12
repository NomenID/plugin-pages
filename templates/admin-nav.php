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
 * @var string $title
 * @var string $summary
 * @var string $sort
 * @var bool $collapsed
 * @var string $mode
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
        <span class="a-label">侧边栏方式</span>
        <label class="a-check">
            <input type="radio" name="mode" value="manual" <?= $mode !== 'auto' ? 'checked' : '' ?>>
            <span>手动写条目</span>
        </label>
        <label class="a-check">
            <input type="radio" name="mode" value="auto" <?= $mode === 'auto' ? 'checked' : '' ?>>
            <span>按目录自动生成</span>
        </label>
        <p class="a-hint">自动生成按 slug 树和排序拼出来，新页面不用回来改；下面的条目框会被忽略。</p>
    </div>

    <div class="a-field">
        <label class="a-check">
            <input type="checkbox" name="enabled" value="1" <?= $enabled ? 'checked' : '' ?>>
            <span>在这个目录显示侧边栏</span>
        </label>
    </div>

    <?php if ($section !== ''): ?>
    <div class="a-field">
        <label class="a-label" for="nav_title">目录标题</label>
        <input id="nav_title" class="a-input" name="title" value="<?= htmlspecialchars($title) ?>">
        <p class="a-hint">分组标题。留空就用该目录 index 页的标题，再没有就把地址转成空格。</p>
    </div>

    <div class="a-field">
        <label class="a-label" for="nav_summary">目录摘要</label>
        <input id="nav_summary" class="a-input" name="summary" value="<?= htmlspecialchars($summary) ?>">
    </div>

    <div class="a-field">
        <label class="a-label" for="nav_sort">排序</label>
        <input id="nav_sort" class="a-input" type="number" name="sort_order" value="<?= htmlspecialchars($sort) ?>">
        <p class="a-hint">数字小的在前。留空就跟着组里最靠前的页面。</p>
    </div>

    <div class="a-field">
        <label class="a-check">
            <input type="checkbox" name="collapsed" value="1" <?= $collapsed ? 'checked' : '' ?>>
            <span>被上级边栏当成一组时默认收起（当前页在里面时自动展开）</span>
        </label>
    </div>
    <?php endif; ?>

    <div class="a-field">
        <label class="a-label" for="nav_items">侧边栏条目</label>
        <textarea id="nav_items" class="a-input a-textarea a-mono" name="items" rows="14"
                  placeholder="概览 | local-docs/index&#10;  安装 | local-docs/install&#10;  升级 | local-docs/upgrade"><?= htmlspecialchars($text) ?></textarea>
        <p class="a-hint">
            手动方式才用这里。一行一个条目：<code>标题 | 目标</code>。缩进两个空格表示下一级，第三列写 <code>hide</code> 就不在公开页面显示。
            目标写页面 slug（如 <code>local-docs/install</code>），也可以写完整网址。
        </p>
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
