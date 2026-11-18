<div class="admin-layout">
    <?php require __DIR__ . '/_nav.php'; ?>
    <div>
        <section class="page-heading">
            <div>
                <span class="section-code">SITE / NAVIGATION</span>
                <h1><?= e(t('Navigation settings')) ?></h1>
                <p><?= e(t('Add, hide, reorder or remove the links shown in the top menu.')) ?></p>
            </div>
        </section>

        <section class="section-block compact-section">
            <div class="section-heading">
                <div><span class="section-code">MENU ITEMS</span><h2><?= e(t('Top menu')) ?></h2></div>
                <span class="section-count"><?= count($items) ?></span>
            </div>
            <?php foreach ($items as $item): ?>
                <?php $fid = 'nav-' . (int) $item['id']; ?>
                <form id="<?= $fid ?>" method="post" action="/admin/navigation/items/<?= (int) $item['id'] ?>"><?= csrf_field() ?></form>
                <form id="<?= $fid ?>-delete" method="post" action="/admin/navigation/items/<?= (int) $item['id'] ?>/delete" data-confirm="<?= e(t('Delete this menu item?')) ?>"><?= csrf_field() ?></form>
            <?php endforeach; ?>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead><tr><th><?= e(t('Name')) ?></th><th>URL</th><th><?= e(t('Position')) ?></th><th><?= e(t('Visible')) ?></th><th><?= e(t('Access')) ?></th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php $fid = 'nav-' . (int) $item['id']; ?>
                        <tr>
                            <td><input form="<?= $fid ?>" name="label" value="<?= e($item['label']) ?>" placeholder="<?= e($item['label_key'] !== '' ? t($item['label_key']) : '') ?>"></td>
                            <td><input form="<?= $fid ?>" name="url" value="<?= e($item['url']) ?>" required></td>
                            <td><input form="<?= $fid ?>" type="number" name="position" value="<?= (int) $item['position'] ?>"></td>
                            <td><label class="check-inline"><input form="<?= $fid ?>" type="checkbox" name="is_visible" value="1" <?= (int) $item['is_visible'] === 1 ? 'checked' : '' ?>> <?= e(t('Show')) ?></label></td>
                            <td class="nav-access">
                                <?php if ((int) $item['requires_admin'] === 1): ?><span class="badge"><?= e(t('Admin only')) ?></span><?php elseif ((int) $item['requires_auth'] === 1): ?><span class="badge"><?= e(t('Signed in')) ?></span><?php else: ?><span class="badge"><?= e(t('Everyone')) ?></span><?php endif; ?>
                            </td>
                            <td class="table-actions">
                                <button form="<?= $fid ?>" class="btn-mini" type="submit"><?= e(t('Save')) ?></button>
                                <?php if ((int) $item['is_core'] !== 1): ?>
                                    <button form="<?= $fid ?>-delete" class="btn-mini danger" type="submit"><?= e(t('Delete')) ?></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <form class="inline-create" method="post" action="/admin/navigation/items">
                <?= csrf_field() ?>
                <input name="label" placeholder="<?= e(t('Menu label')) ?>" required>
                <input name="url" placeholder="/path or https://..." required>
                <input type="number" name="position" placeholder="<?= e(t('Position')) ?>" value="0">
                <label class="check-inline"><input type="checkbox" name="is_visible" value="1" checked> <?= e(t('Show')) ?></label>
                <label class="check-inline"><input type="checkbox" name="requires_auth" value="1"> <?= e(t('Signed in')) ?></label>
                <label class="check-inline"><input type="checkbox" name="requires_admin" value="1"> <?= e(t('Admin only')) ?></label>
                <button class="btn btn-primary" type="submit">+ <?= e(t('Add menu item')) ?></button>
            </form>
        </section>
    </div>
</div>
