<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE file in this repository for the full text.
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

    <?php if ($groups === []): ?>
        <p class="docs-empty"><?= t('No pages yet.') ?></p>
    <?php else: ?>
        <ul class="docs-list">
            <?php foreach ($groups as $item): ?>
                <li>
                    <a class="docs-list__title" href="<?= htmlspecialchars($base . '/' . $item['href']) ?>"><?= htmlspecialchars((string)$item['title']) ?></a>
                    <?php if ($item['type'] === 'dir'): ?>
                        <span class="docs-list__count">(<?= (int)$item['count'] ?>)</span>
                    <?php endif; ?>
                    <?php if ((int)$item['status'] === 0): ?><span class="docs-badge"><?= t('Draft') ?></span><?php endif; ?>
                    <?php if ((string)$item['summary'] !== ''): ?>
                        <span class="docs-list__summary"><?= htmlspecialchars((string)$item['summary']) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
