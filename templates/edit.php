<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the GNU General Public License, version 3 only, with
 * additional attribution terms in the NOTICE file.
 * Source: https://github.com/NomenID/plugin-pages
 * Project: https://nomenid.org/w/plugin-pages
 */

/** @var array $page @var bool $isNew @var string|null $error @var string $base */
$actionSlug = (string)($page['action_slug'] ?? $page['slug'] ?? '');
$action = $isNew ? $base . '/new' : $base . '/' . $actionSlug . '/edit';
$cancel = $isNew ? $base : $base . '/' . $actionSlug;
$confirm = htmlspecialchars(t('Delete this page?'), ENT_QUOTES);
?>
<div class="docs-edit">
    <div class="docs-head">
        <h1><?= $isNew ? t('New page') : t('Edit page') ?></h1>
        <a href="<?= htmlspecialchars($cancel) ?>"><?= t('Back to docs') ?></a>
    </div>

    <?php if ($error !== null && $error !== ''): ?>
        <div class="docs-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($action) ?>" class="docs-form">
        <?= \Nomen\Src\Csrf::field() ?>

        <div class="form-group">
            <label for="page-title"><?= t('Title') ?></label>
            <input id="page-title" name="title" value="<?= htmlspecialchars((string)($page['title'] ?? '')) ?>" required maxlength="255">
        </div>

        <div class="form-group">
            <label for="page-slug"><?= t('Address') ?></label>
            <input id="page-slug" name="slug" value="<?= htmlspecialchars((string)($page['slug'] ?? '')) ?>" maxlength="100" pattern="[a-z0-9][a-z0-9-]*">
            <p class="form-hint"><?= t('Lowercase letters, digits, and hyphens; leave blank to generate one from the title.') ?></p>
        </div>

        <div class="form-group">
            <label for="page-summary"><?= t('Summary') ?></label>
            <input id="page-summary" name="summary" value="<?= htmlspecialchars((string)($page['summary'] ?? '')) ?>" maxlength="500">
            <p class="form-hint"><?= t('Shown in the docs list; one line is enough.') ?></p>
        </div>

        <div class="form-group">
            <label for="page-body"><?= t('Body') ?>（Markdown）</label>
            <textarea id="page-body" name="body" rows="22" spellcheck="false"><?= htmlspecialchars((string)($page['body'] ?? '')) ?></textarea>
            <p class="form-hint"><?= t('Supports headings, lists, code blocks, tables, links, and images; HTML is shown as text.') ?></p>
        </div>

        <div class="docs-form__row">
            <div class="form-group">
                <label for="page-sort"><?= t('Order') ?></label>
                <input id="page-sort" name="sort_order" type="number" value="<?= (int)($page['sort_order'] ?? 0) ?>">
            </div>
            <div class="form-group">
                <label for="page-status"><?= t('Status') ?></label>
                <select id="page-status" name="status">
                    <option value="1"<?= (int)($page['status'] ?? 1) === 1 ? ' selected' : '' ?>><?= t('Published') ?></option>
                    <option value="0"<?= (int)($page['status'] ?? 1) === 0 ? ' selected' : '' ?>><?= t('Draft') ?></option>
                </select>
            </div>
        </div>

        <div class="docs-form__actions">
            <button type="submit" class="btn btn-primary"><?= t('Save') ?></button>
            <a class="btn btn-secondary" href="<?= htmlspecialchars($cancel) ?>"><?= t('Cancel') ?></a>
        </div>
    </form>

    <?php if (!$isNew): ?>
        <form method="post" action="<?= htmlspecialchars($base . '/' . $actionSlug . '/delete') ?>" class="docs-delete" onsubmit="return confirm('<?= $confirm ?>');">
            <?= \Nomen\Src\Csrf::field() ?>
            <button type="submit" class="btn btn-danger"><?= t('Delete') ?></button>
        </form>
    <?php endif; ?>
</div>
