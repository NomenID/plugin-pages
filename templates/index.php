<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the GNU General Public License, version 3 only, with
 * additional attribution terms in the NOTICE file.
 * Source: https://github.com/NomenID/plugin-pages
 * Project: https://nomenid.org/w/plugin-pages
 */

/** @var array $pages @var string $base @var bool $isAdmin @var string|null $notice */
?>
<div class="docs-index">
    <div class="docs-head">
        <h1><?= t('Docs') ?></h1>
        <?php if ($isAdmin): ?>
            <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($base) ?>/new"><?= t('New page') ?></a>
        <?php endif; ?>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="docs-notice"><?= htmlspecialchars((string)$notice) ?></div>
    <?php endif; ?>

    <p class="docs-lead"><?= t('Install, integrate, and extend Nomen.') ?></p>

    <?php if ($pages === []): ?>
        <p class="docs-empty"><?= t('No pages yet.') ?></p>
    <?php else: ?>
        <ul class="docs-list">
            <?php foreach ($pages as $page): ?>
                <li>
                    <a class="docs-list__title" href="<?= htmlspecialchars($base . '/' . $page['slug']) ?>"><?= htmlspecialchars((string)$page['title']) ?></a>
                    <?php if ((int)$page['status'] === 0): ?><span class="docs-badge"><?= t('Draft') ?></span><?php endif; ?>
                    <?php if ((string)$page['summary'] !== ''): ?>
                        <span class="docs-list__summary"><?= htmlspecialchars((string)$page['summary']) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
