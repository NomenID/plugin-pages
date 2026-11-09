<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the GNU General Public License, version 3 only, with
 * additional attribution terms in the NOTICE file.
 * Source: https://github.com/NomenID/plugin-pages
 * Project: https://nomen.caellab.com/w/plugin-pages
 */

/** @var array $page @var array $pages @var string $base @var bool $isAdmin @var string $article @var string|null $notice */
$current = (string)$page['slug'];
?>
<div class="docs-layout">
    <aside class="docs-nav">
        <a class="docs-nav__home" href="<?= htmlspecialchars($base) ?>"><?= t('All docs') ?></a>
        <ul>
            <?php foreach ($pages as $item): ?>
                <li<?= $item['slug'] === $current ? ' class="is-current"' : '' ?>>
                    <a href="<?= htmlspecialchars($base . '/' . $item['slug']) ?>"><?= htmlspecialchars((string)$item['title']) ?></a>
                    <?php if ((int)$item['status'] === 0): ?><span class="docs-badge"><?= t('Draft') ?></span><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>

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
