<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE file in this repository for the full text.
 * Source: https://github.com/NomenID/plugin-pages
 * Project: https://nomenid.org/w/plugin-pages
 */

/** @var array $page @var array $pages @var string $base @var bool $isAdmin @var string $article @var string|null $notice */
$current = (string)$page['slug'];
?>
<div class="docs-layout<?= empty($menu) ? ' docs-layout--solo' : '' ?>">
    <?php if (!empty($menu)): ?>
        <aside class="docs-nav">
            <a class="docs-nav__home" href="<?= htmlspecialchars($base) ?>"><?= t('All docs') ?></a>
            <?= \Nomen\Plugin\Pages\Menu::render($menu, $current, $base) ?>
        </aside>
    <?php endif; ?>

    <article class="docs-article">
        <div class="docs-head">
            <h1><?= htmlspecialchars((string)$page['title']) ?></h1>
            <div class="docs-meta">
                <?php if (!empty($page['updated_at'])): ?>
                    <span><?= t('Updated {:date}', ['date' => substr((string)$page['updated_at'], 0, 10)]) ?></span>
                <?php endif; ?>
                <?php if ((int)$page['status'] === 0): ?><span class="docs-badge"><?= t('Draft') ?></span><?php endif; ?>
                <?php if ($isAdmin): ?>
                    <a href="<?= htmlspecialchars($base . '/' . $page['slug'] . '/edit') ?>"><?= t('Edit') ?></a>
                    <a href="/admin/logs?target=<?= urlencode($current) ?>"><?= t('Page log') ?></a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($notice)): ?>
            <div class="docs-notice"><?= htmlspecialchars((string)$notice) ?></div>
        <?php endif; ?>

        <div class="docs-body"><?= $article ?></div>
    </article>
</div>
