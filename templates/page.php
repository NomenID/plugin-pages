<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE file in this repository for the full text.
 * Source: https://github.com/NomenID/plugin-pages
 * Project: https://nomenid.org/w/plugin-pages
 */

use Nomen\Plugin\Pages\Nav;

/** @var array $page @var array $pages @var string $base @var bool $isAdmin @var string $article @var string|null $notice @var array|null $menu @var string $menuMode @var array $toc @var array $crumbs @var array{prev:?array,next:?array} $pager */
$current = (string)$page['slug'];
$hasToc = $toc !== [];
$layout = 'docs-layout' . (empty($menu) ? ' docs-layout--solo' : '') . ($hasToc ? ' docs-layout--toc' : '');
?>
<div class="<?= $layout ?>">
    <?php if (!empty($menu)): ?>
        <aside class="docs-nav">
            <a class="docs-nav__home" href="<?= htmlspecialchars($base) ?>"><?= t('All docs') ?></a>
            <?= ($menuMode ?? 'manual') === 'auto'
                ? \Nomen\Plugin\Pages\Menu::renderAuto($menu, $current, $base)
                : \Nomen\Plugin\Pages\Menu::render($menu, $current, $base) ?>
        </aside>
    <?php endif; ?>

    <article class="docs-article">
        <nav class="docs-crumbs" aria-label="<?= htmlspecialchars(t('Docs')) ?>">
            <?php foreach ($crumbs as $i => $crumb): ?>
                <?php if ($i > 0): ?><span class="docs-crumbs__sep">/</span><?php endif; ?>
                <?php if (!empty($crumb['href'])): ?>
                    <a href="<?= htmlspecialchars((string)$crumb['href']) ?>"><?= htmlspecialchars((string)$crumb['label']) ?></a>
                <?php else: ?>
                    <span><?= htmlspecialchars((string)$crumb['label']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

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

        <?php if ($hasToc): ?>
            <details class="docs-toc-mobile">
                <summary><?= t('On this page') ?></summary>
                <?= Nav::tocList($toc) ?>
            </details>
        <?php endif; ?>

        <div class="docs-body"><?= $article ?></div>

        <?php if ($pager['prev'] !== null || $pager['next'] !== null): ?>
            <nav class="docs-pager">
                <?php if ($pager['prev'] !== null): ?>
                    <a class="docs-pager__prev" href="<?= htmlspecialchars($base . '/' . $pager['prev']['slug']) ?>">
                        <span class="docs-pager__label">← <?= t('Previous page') ?></span>
                        <span class="docs-pager__title"><?= htmlspecialchars((string)$pager['prev']['label']) ?></span>
                    </a>
                <?php endif; ?>
                <?php if ($pager['next'] !== null): ?>
                    <a class="docs-pager__next" href="<?= htmlspecialchars($base . '/' . $pager['next']['slug']) ?>">
                        <span class="docs-pager__label"><?= t('Next page') ?> →</span>
                        <span class="docs-pager__title"><?= htmlspecialchars((string)$pager['next']['label']) ?></span>
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </article>

    <?php if ($hasToc): ?>
        <aside class="docs-toc">
            <div class="docs-toc__title"><?= t('On this page') ?></div>
            <?= Nav::tocList($toc) ?>
        </aside>
    <?php endif; ?>
</div>
