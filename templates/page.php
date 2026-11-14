<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE file in this repository for the full text.
 * Source: https://github.com/NomenID/plugin-pages
 * Project: https://nomenid.org/w/plugin-pages
 */

use Nomen\Plugin\Pages\Nav;

/** @var array $page @var array $pages @var string $base @var bool $isAdmin @var string $article @var string|null $notice @var array|null $menu @var string $menuMode @var array $toc @var array $crumbs @var array{prev:?array,next:?array} $pager @var array{nav:bool,toc:bool} $panes */
$current = (string)$page['slug'];
$hasToc = $toc !== [];
$navHidden = !empty($menu) && !empty($panes['nav']);
$tocHidden = $hasToc && !empty($panes['toc']);
$layout = 'docs-layout'
    . (empty($menu) ? ' docs-layout--solo' : '')
    . ($hasToc ? ' docs-layout--toc' : '')
    . ($navHidden ? ' docs-layout--nav-hidden' : '')
    . ($tocHidden ? ' docs-layout--toc-hidden' : '');
?>
<div class="<?= $layout ?>" data-docs-base="<?= htmlspecialchars($base) ?>">
    <?php if (!empty($menu)): ?>
        <aside class="docs-nav" id="docs-nav">
            <div class="docs-nav__head">
                <a class="docs-nav__home" href="<?= htmlspecialchars($base) ?>"><?= t('All docs') ?></a>
                <button type="button" class="docs-pane-btn" data-pane="nav" aria-controls="docs-nav" aria-expanded="<?= $navHidden ? 'false' : 'true' ?>"><?= t('Hide') ?></button>
            </div>
            <?= ($menuMode ?? 'manual') === 'auto'
                ? \Nomen\Plugin\Pages\Menu::renderAuto($menu, $current, $base)
                : \Nomen\Plugin\Pages\Menu::render($menu, $current, $base) ?>
        </aside>
    <?php endif; ?>

    <article class="docs-article">
        <div class="docs-titlebar">
            <?php if (!empty($menu)): ?>
                <button type="button" class="docs-pane-btn docs-pane-btn--reveal docs-reveal--nav" data-pane="nav" aria-controls="docs-nav" aria-expanded="<?= $navHidden ? 'false' : 'true' ?>" aria-label="<?= htmlspecialchars(t('Show navigation')) ?>" title="<?= htmlspecialchars(t('Show navigation')) ?>">
                    <svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><path d="M10 3 5 8l5 5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            <?php endif; ?>
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
            <?php if ($hasToc): ?>
                <button type="button" class="docs-pane-btn docs-pane-btn--reveal docs-reveal--toc" data-pane="toc" aria-controls="docs-toc" aria-expanded="<?= $tocHidden ? 'false' : 'true' ?>" aria-label="<?= htmlspecialchars(t('Show page contents')) ?>" title="<?= htmlspecialchars(t('Show page contents')) ?>">
                    <svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><path d="M6 3l5 5-5 5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            <?php endif; ?>
        </div>

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
        <aside class="docs-toc" id="docs-toc">
            <div class="docs-toc__head">
                <div class="docs-toc__title"><?= t('On this page') ?></div>
                <button type="button" class="docs-pane-btn" data-pane="toc" aria-controls="docs-toc" aria-expanded="<?= $tocHidden ? 'false' : 'true' ?>"><?= t('Hide') ?></button>
            </div>
            <?= Nav::tocList($toc) ?>
        </aside>
    <?php endif; ?>
</div>
