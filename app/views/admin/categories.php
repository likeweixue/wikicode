<?php $iconKeys = array_keys(\App\Core\Icons::all()); ?>
<div class="admin-layout">
    <?php require __DIR__ . '/_nav.php'; ?>
    <div>
        <section class="page-heading">
            <div>
                <span class="section-code">CATEGORIES / EXPLORE</span>
                <h1><?= e(t('Category settings')) ?></h1>
                <p><?= e(t('Create and organize repository categories shown in Explore.')) ?></p>
            </div>
            <a class="btn btn-ghost" href="/explore" target="_blank" rel="noopener"><?= e(t('Explore')) ?> ↗</a>
        </section>

        <section class="section-block compact-section">
            <div class="section-heading">
                <div><span class="section-code">CATEGORIES</span><h2><?= e(t('Categories')) ?></h2></div>
                <span class="section-count"><?= count($categories) ?></span>
            </div>
            <?php foreach ($categories as $category): ?>
                <?php $fid = 'cat-' . (int) $category['id']; ?>
                <form id="<?= $fid ?>" method="post" action="/admin/categories/<?= (int) $category['id'] ?>"><?= csrf_field() ?></form>
                <form id="<?= $fid ?>-delete" method="post" action="/admin/categories/<?= (int) $category['id'] ?>/delete" data-confirm="<?= e(t('Delete this category? Repositories in it will become uncategorized.')) ?>"><?= csrf_field() ?></form>
            <?php endforeach; ?>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead><tr><th><?= e(t('Icon')) ?></th><th><?= e(t('Name')) ?></th><th><?= e(t('Description')) ?></th><th><?= e(t('Position')) ?></th><th><?= e(t('Built-in')) ?></th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($categories as $category): ?>
                        <?php $fid = 'cat-' . (int) $category['id']; $isBuiltin = !empty($category['is_builtin']); ?>
                        <tr>
                            <td><span class="icon-preview"><?= \App\Services\CategoryService::iconSvg((string) $category['icon']) ?></span><select form="<?= $fid ?>" name="icon"><?php foreach ($iconKeys as $k): ?><option value="<?= e($k) ?>" <?= ((string) $category['icon']) === $k ? 'selected' : '' ?>><?= e($k) ?></option><?php endforeach; ?></select></td>
                            <td><input form="<?= $fid ?>" name="name" value="<?= e((string) $category['name']) ?>" required></td>
                            <td><input form="<?= $fid ?>" name="description" value="<?= e((string) $category['description']) ?>"></td>
                            <td><input form="<?= $fid ?>" type="number" name="position" value="<?= (int) $category['position'] ?>" style="width:70px"></td>
                            <td><?= $isBuiltin ? '✓' : '' ?></td>
                            <td class="table-actions">
                                <button form="<?= $fid ?>" class="btn-mini" type="submit"><?= e(t('Save')) ?></button>
                                <?php if (!$isBuiltin): ?><button form="<?= $fid ?>-delete" class="btn-mini danger" type="submit"><?= e(t('Delete')) ?></button><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <form class="inline-create" method="post" action="/admin/categories">
                <?= csrf_field() ?>
                <select name="icon"><?php foreach ($iconKeys as $k): ?><option value="<?= e($k) ?>" <?= $k === 'other' ? 'selected' : '' ?>><?= e($k) ?></option><?php endforeach; ?></select>
                <input name="name" placeholder="<?= e(t('New category name')) ?>" required>
                <input name="description" placeholder="<?= e(t('Description')) ?>">
                <input type="number" name="position" placeholder="<?= e(t('Position')) ?>" value="0" style="width:70px">
                <button class="btn btn-primary" type="submit">+ <?= e(t('Add category')) ?></button>
            </form>
        </section>
    </div>
</div>
