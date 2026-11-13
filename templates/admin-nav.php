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
 * @var string $mode
 * @var string $base
 * @var string $csrf
 * @var array $pages
 * @var string $notice
 */
$label = static fn(string $path): string => $path === '' ? t('Root ({:base})', ['base' => (string)$base]) : $path;
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
        <span class="a-label"><?= t('Sidebar mode') ?></span>
        <label class="a-check">
            <input type="radio" name="mode" value="manual" <?= $mode !== 'auto' ? 'checked' : '' ?>>
            <span><?= t('Write entries by hand') ?></span>
        </label>
        <label class="a-check">
            <input type="radio" name="mode" value="auto" <?= $mode === 'auto' ? 'checked' : '' ?>>
            <span><?= t('Build it from the directory') ?></span>
        </label>
        <p class="a-hint"><?= t('Automatic mode builds the sidebar from the slug tree and sort order, so new pages show up without editing this form. The box below is ignored.') ?></p>
    </div>

    <div class="a-field">
        <label class="a-check">
            <input type="checkbox" name="enabled" value="1" <?= $enabled ? 'checked' : '' ?>>
            <span><?= t('Show a sidebar in this directory') ?></span>
        </label>
    </div>

    <?php if ($section !== ''): ?>
    <div class="a-field">
        <label class="a-label" for="nav_title"><?= t('Directory title') ?></label>
        <input id="nav_title" class="a-input" name="title" value="<?= htmlspecialchars($title) ?>">
        <p class="a-hint"><?= t('Group heading. Empty means the title of the directory index page, or the slug with spaces.') ?></p>
    </div>

    <div class="a-field">
        <label class="a-label" for="nav_summary"><?= t('Directory summary') ?></label>
        <input id="nav_summary" class="a-input" name="summary" value="<?= htmlspecialchars($summary) ?>">
    </div>

    <div class="a-field">
        <label class="a-label" for="nav_sort"><?= t('Sort order') ?></label>
        <input id="nav_sort" class="a-input" type="number" name="sort_order" value="<?= htmlspecialchars($sort) ?>">
        <p class="a-hint"><?= t('Smaller numbers come first. Empty follows the first page in the group.') ?></p>
    </div>

    <?php endif; ?>
    <div class="a-field">
        <label class="a-label" for="nav_items"><?= t('Sidebar entries') ?></label>
        <textarea id="nav_items" class="a-input a-textarea a-mono" name="items" rows="14"
                  placeholder="Overview | local-docs/index&#10;  Install | local-docs/install&#10;  Upgrade | local-docs/upgrade"><?= htmlspecialchars($text) ?></textarea>
        <p class="a-hint">
            <?= t('Manual mode only. One entry per line: <code>title | target</code>. Indent two spaces for a sub-level; add <code>hide</code> as a third column to keep the entry out of the public page. The target is a page slug (like <code>local-docs/install</code>) or a full URL.') ?>
        </p>
    </div>

    <div class="a-actions">
        <button class="a-btn a-btn--primary" type="submit"><?= t('Save') ?></button>
    </div>
</form>

<div class="a-field" style="margin-top:24px">
    <span class="a-label"><?= t('Available page slugs') ?></span>
    <p class="a-hint">
        <?php foreach ($pages as $page): ?>
            <code><?= htmlspecialchars((string)$page['slug']) ?></code>
        <?php endforeach; ?>
    </p>
</div>
