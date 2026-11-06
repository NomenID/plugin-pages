<?php
/** @var array $page @var bool $isNew @var string|null $error @var string $base */
$actionSlug = (string)($page['action_slug'] ?? $page['slug'] ?? '');
$action = $isNew ? $base . '/new' : $base . '/' . $actionSlug . '/edit';
$cancel = $isNew ? $base : $base . '/' . $actionSlug;
$confirm = htmlspecialchars(t('确定删除这个页面？'), ENT_QUOTES);
?>
<div class="docs-edit">
    <div class="docs-head">
        <h1><?= $isNew ? t('新建页面') : t('编辑页面') ?></h1>
        <a href="<?= htmlspecialchars($cancel) ?>"><?= t('返回文档') ?></a>
    </div>

    <?php if ($error !== null && $error !== ''): ?>
        <div class="docs-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($action) ?>" class="docs-form">
        <?= \Nomen\Src\Csrf::field() ?>

        <div class="form-group">
            <label for="page-title"><?= t('标题') ?></label>
            <input id="page-title" name="title" value="<?= htmlspecialchars((string)($page['title'] ?? '')) ?>" required maxlength="255">
        </div>

        <div class="form-group">
            <label for="page-slug"><?= t('地址') ?></label>
            <input id="page-slug" name="slug" value="<?= htmlspecialchars((string)($page['slug'] ?? '')) ?>" maxlength="100" pattern="[a-z0-9][a-z0-9-]*">
            <p class="form-hint"><?= t('小写字母、数字和连字符；留空会从标题生成。') ?></p>
        </div>

        <div class="form-group">
            <label for="page-summary"><?= t('摘要') ?></label>
            <input id="page-summary" name="summary" value="<?= htmlspecialchars((string)($page['summary'] ?? '')) ?>" maxlength="500">
            <p class="form-hint"><?= t('显示在文档列表里，一句话就够。') ?></p>
        </div>

        <div class="form-group">
            <label for="page-body"><?= t('正文') ?>（Markdown）</label>
            <textarea id="page-body" name="body" rows="22" spellcheck="false"><?= htmlspecialchars((string)($page['body'] ?? '')) ?></textarea>
            <p class="form-hint"><?= t('支持标题、列表、代码块、表格、链接和图片；HTML 会原样显示。') ?></p>
        </div>

        <div class="docs-form__row">
            <div class="form-group">
                <label for="page-sort"><?= t('排序') ?></label>
                <input id="page-sort" name="sort_order" type="number" value="<?= (int)($page['sort_order'] ?? 0) ?>">
            </div>
            <div class="form-group">
                <label for="page-status"><?= t('状态') ?></label>
                <select id="page-status" name="status">
                    <option value="1"<?= (int)($page['status'] ?? 1) === 1 ? ' selected' : '' ?>><?= t('发布') ?></option>
                    <option value="0"<?= (int)($page['status'] ?? 1) === 0 ? ' selected' : '' ?>><?= t('草稿') ?></option>
                </select>
            </div>
        </div>

        <div class="docs-form__actions">
            <button type="submit" class="btn btn-primary"><?= t('保存') ?></button>
            <a class="btn btn-secondary" href="<?= htmlspecialchars($cancel) ?>"><?= t('取消') ?></a>
        </div>
    </form>

    <?php if (!$isNew): ?>
        <form method="post" action="<?= htmlspecialchars($base . '/' . $actionSlug . '/delete') ?>" class="docs-delete" onsubmit="return confirm('<?= $confirm ?>');">
            <?= \Nomen\Src\Csrf::field() ?>
            <button type="submit" class="btn btn-danger"><?= t('删除') ?></button>
        </form>
    <?php endif; ?>
</div>
