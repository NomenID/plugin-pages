<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE file in this repository for the full text.
 * Source: https://github.com/NomenID/plugin-pages
 * Project: https://nomenid.org/w/plugin-pages
 */

/**
 * @var array $page
 * @var bool $isNew
 * @var string $base
 * @var string $csrf
 * @var string $error
 * @var string $extras
 */
$action = $isNew
    ? $base . '/new'
    : $base . '/' . implode('/', array_map('rawurlencode', explode('/', (string)$page['slug']))) . '/edit';
$cancel = '/admin/content?tab=all';
?>
<?php if ($error !== ''): ?>
    <div class="a-alert a-alert--danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($action) ?>" data-admin-ajax data-admin-redirect="/admin/content?tab=all">
    <?= $csrf ?>
    <input type="hidden" name="_return" value="admin">

    <div class="a-form">
        <div class="a-form__row">
            <label class="a-field">
                <span class="a-field__label"><?= t('Title') ?></span>
                <input class="a-input" type="text" name="title" value="<?= htmlspecialchars((string)$page['title']) ?>" maxlength="255" required>
            </label>
            <label class="a-field">
                <span class="a-field__label"><?= t('Slug') ?></span>
                <input class="a-input a-mono" type="text" name="slug" value="<?= htmlspecialchars((string)$page['slug']) ?>" placeholder="<?= t('Leave empty to build it from the title') ?>">
                <span class="a-field__hint"><?= t('Lowercase letters, digits and hyphens. The page will live at {:base}/<slug>.', ['base' => (string)$base]) ?></span>
            </label>
        </div>

        <label class="a-field">
            <span class="a-field__label"><?= t('Summary') ?></span>
            <textarea class="a-input a-textarea" name="summary" rows="2" maxlength="500"><?= htmlspecialchars((string)$page['summary']) ?></textarea>
        </label>

        <label class="a-field">
            <span class="a-field__label"><?= t('Body (Markdown)') ?></span>
            <textarea class="a-input a-textarea a-mono" name="body" rows="18" required><?= htmlspecialchars((string)$page['body']) ?></textarea>
        </label>

        <div class="a-form__row">
            <label class="a-field">
                <span class="a-field__label"><?= t('Status') ?></span>
                <select class="a-input" name="status">
                    <option value="1" <?= (int)$page['status'] === 1 ? 'selected' : '' ?>><?= t('Published') ?></option>
                    <option value="0" <?= (int)$page['status'] !== 1 ? 'selected' : '' ?>><?= t('Draft') ?></option>
                </select>
            </label>
            <label class="a-field">
                <span class="a-field__label"><?= t('Sort order') ?></span>
                <input class="a-input" type="number" name="sort_order" value="<?= (int)$page['sort_order'] ?>" step="1">
                <span class="a-field__hint"><?= t('Smaller numbers come first') ?></span>
            </label>
        </div>

        <?= $extras ?>
    </div>

    <div class="a-form__actions">
        <button class="a-btn a-btn--primary" type="submit"><?= $isNew ? t('Create page') : t('Save changes') ?></button>
        <a class="a-btn a-btn--ghost" href="<?= htmlspecialchars($cancel) ?>"><?= t('Cancel') ?></a>
        <?php if (!$isNew && (int)$page['status'] === 1): ?>
            <a class="a-btn a-btn--ghost" href="<?= htmlspecialchars($base . '/' . $page['slug']) ?>" target="_blank" rel="noopener"><?= t('View on site') ?></a>
        <?php endif; ?>
    </div>
</form>
