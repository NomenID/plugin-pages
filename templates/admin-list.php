<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE file in this repository for the full text.
 * Source: https://github.com/NomenID/plugin-pages
 * Project: https://nomenid.org/w/plugin-pages
 */

/**
 * @var array $pages
 * @var array $counts
 * @var string $status
 * @var string $notice
 * @var string $base
 */
$tab = htmlspecialchars($status);
?>
<div class="a-plugin-toolbar">
    <a class="a-btn a-btn--primary a-btn--sm" href="/admin/content?tab=<?= $tab ?>&edit=new"><?= t('New page') ?></a>
    <span class="a-text--muted"><?= htmlspecialchars(t('{:count} pages', ['count' => count($pages)])) ?></span>
</div>

<?php if ($notice !== ''): ?>
    <div class="a-alert a-alert--success"><?= htmlspecialchars($notice) ?></div>
<?php endif; ?>

<?php if ($pages === []): ?>
    <div class="a-empty"><?= t('No pages in this tab yet.') ?></div>
<?php else: ?>
    <div class="a-table-wrap">
        <table class="a-table">
            <thead>
                <tr><th><?= t('Title') ?></th><th><?= t('Slug') ?></th><th><?= t('Status') ?></th><th><?= t('Sort order') ?></th><th><?= t('Updated') ?></th><th class="a-td-actions"><?= t('Actions') ?></th></tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $page): ?>
                    <?php $slug = (string)$page['slug']; ?>
                    <tr>
                        <td>
                            <a class="a-link a-link--strong" href="/admin/content?tab=<?= $tab ?>&edit=<?= htmlspecialchars(rawurlencode($slug)) ?>"><?= htmlspecialchars((string)$page['title']) ?></a>
                            <?php if ((string)$page['summary'] !== ''): ?>
                                <div class="a-sub"><?= htmlspecialchars((string)$page['summary']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="a-mono"><?= htmlspecialchars($base . '/' . $slug) ?></td>
                        <td>
                            <?php if ((int)$page['status'] === 1): ?>
                                <span class="a-badge a-badge--success"><?= t('Published') ?></span>
                            <?php else: ?>
                                <span class="a-badge a-badge--muted"><?= t('Draft') ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)$page['sort_order'] ?></td>
                        <td class="a-nowrap"><?= htmlspecialchars((string)$page['updated_at']) ?></td>
                        <td class="a-td-actions">
                            <a class="a-btn a-btn--ghost a-btn--sm" href="/admin/content?tab=<?= $tab ?>&edit=<?= htmlspecialchars(rawurlencode($slug)) ?>"><?= t('Edit') ?></a>
                            <a class="a-btn a-btn--ghost a-btn--sm" href="<?= htmlspecialchars($base . '/' . $slug) ?>" target="_blank" rel="noopener"><?= t('View') ?></a>
                            <form method="post" action="<?= htmlspecialchars($base . '/' . $slug . '/delete') ?>" data-admin-ajax data-admin-confirm="<?= htmlspecialchars(t('Delete "{:title}"? This cannot be undone.', ['title' => (string)$page['title']])) ?>" style="display:inline">
                                <?= \Nomen\Src\Csrf::field() ?>
                                <input type="hidden" name="_return" value="admin">
                                <button class="a-btn a-btn--danger a-btn--sm" type="submit"><?= t('Delete') ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
