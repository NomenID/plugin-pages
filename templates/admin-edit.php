<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the GNU General Public License, version 3 or any later
 * version, with additional attribution terms in the NOTICE file.
 * Source: https://github.com/NomenID/plugin-pages
 * Project: https://nomen.caellab.com/w/plugin-pages
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
    : $base . '/' . rawurlencode((string)$page['slug']) . '/edit';
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
                <span class="a-field__label">标题</span>
                <input class="a-input" type="text" name="title" value="<?= htmlspecialchars((string)$page['title']) ?>" maxlength="255" required>
            </label>
            <label class="a-field">
                <span class="a-field__label">地址</span>
                <input class="a-input a-mono" type="text" name="slug" value="<?= htmlspecialchars((string)$page['slug']) ?>" placeholder="留空按标题生成">
                <span class="a-field__hint">小写字母、数字和连字符；发布地址是 <?= htmlspecialchars($base) ?>/&lt;地址&gt;</span>
            </label>
        </div>

        <label class="a-field">
            <span class="a-field__label">摘要</span>
            <textarea class="a-input a-textarea" name="summary" rows="2" maxlength="500"><?= htmlspecialchars((string)$page['summary']) ?></textarea>
        </label>

        <label class="a-field">
            <span class="a-field__label">正文（Markdown）</span>
            <textarea class="a-input a-textarea a-mono" name="body" rows="18" required><?= htmlspecialchars((string)$page['body']) ?></textarea>
        </label>

        <div class="a-form__row">
            <label class="a-field">
                <span class="a-field__label">状态</span>
                <select class="a-input" name="status">
                    <option value="1" <?= (int)$page['status'] === 1 ? 'selected' : '' ?>>已发布</option>
                    <option value="0" <?= (int)$page['status'] !== 1 ? 'selected' : '' ?>>草稿</option>
                </select>
            </label>
            <label class="a-field">
                <span class="a-field__label">排序</span>
                <input class="a-input" type="number" name="sort_order" value="<?= (int)$page['sort_order'] ?>" step="1">
                <span class="a-field__hint">数字小的排在前面</span>
            </label>
        </div>

        <?= $extras ?>
    </div>

    <div class="a-form__actions">
        <button class="a-btn a-btn--primary" type="submit"><?= $isNew ? '创建页面' : '保存修改' ?></button>
        <a class="a-btn a-btn--ghost" href="<?= htmlspecialchars($cancel) ?>">取消</a>
        <?php if (!$isNew && (int)$page['status'] === 1): ?>
            <a class="a-btn a-btn--ghost" href="<?= htmlspecialchars($base . '/' . $page['slug']) ?>" target="_blank" rel="noopener">在站点查看</a>
        <?php endif; ?>
    </div>
</form>
