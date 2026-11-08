<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the GNU General Public License, version 3 or any later
 * version, with additional attribution terms in the NOTICE file.
 * Source: https://github.com/NomenID/plugin-pages
 * Project: https://nomen.caellab.com/w/plugin-pages
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
    <a class="a-btn a-btn--primary a-btn--sm" href="/admin/content?tab=<?= $tab ?>&edit=new">新建页面</a>
    <span class="a-text--muted">共 <?= count($pages) ?> 篇</span>
</div>

<?php if ($notice !== ''): ?>
    <div class="a-alert a-alert--success"><?= htmlspecialchars($notice) ?></div>
<?php endif; ?>

<?php if ($pages === []): ?>
    <div class="a-empty">这个分类下还没有页面。</div>
<?php else: ?>
    <div class="a-table-wrap">
        <table class="a-table">
            <thead>
                <tr><th>标题</th><th>地址</th><th>状态</th><th>排序</th><th>更新时间</th><th class="a-td-actions">操作</th></tr>
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
                                <span class="a-badge a-badge--success">已发布</span>
                            <?php else: ?>
                                <span class="a-badge a-badge--muted">草稿</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)$page['sort_order'] ?></td>
                        <td class="a-nowrap"><?= htmlspecialchars((string)$page['updated_at']) ?></td>
                        <td class="a-td-actions">
                            <a class="a-btn a-btn--ghost a-btn--sm" href="/admin/content?tab=<?= $tab ?>&edit=<?= htmlspecialchars(rawurlencode($slug)) ?>">编辑</a>
                            <a class="a-btn a-btn--ghost a-btn--sm" href="<?= htmlspecialchars($base . '/' . $slug) ?>" target="_blank" rel="noopener">查看</a>
                            <form method="post" action="<?= htmlspecialchars($base . '/' . $slug . '/delete') ?>" data-admin-ajax data-admin-confirm="删除后无法恢复，确定删除「<?= htmlspecialchars((string)$page['title']) ?>」？" style="display:inline">
                                <?= \Nomen\Src\Csrf::field() ?>
                                <input type="hidden" name="_return" value="admin">
                                <button class="a-btn a-btn--danger a-btn--sm" type="submit">删除</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
