<div class="admin-layout">
    <?php require __DIR__ . '/_nav.php'; ?>
    <div>
        <section class="page-heading">
            <div>
                <span class="section-code">COMMUNITY / STRUCTURE</span>
                <h1><?= e(t('Community settings')) ?></h1>
                <p><?= e(t('Create and organize forum categories and boards.')) ?></p>
            </div>
            <a class="btn btn-ghost" href="/community" target="_blank" rel="noopener"><?= e(t('View community')) ?> ↗</a>
        </section>

        <section class="section-block compact-section">
            <div class="section-heading">
                <div><span class="section-code">CATEGORIES</span><h2><?= e(t('Categories')) ?></h2></div>
                <span class="section-count"><?= count($categories) ?></span>
            </div>
            <?php foreach ($categories as $category): ?>
                <?php $fid = 'category-' . (int) $category['id']; ?>
                <form id="<?= $fid ?>" method="post" action="/admin/community/categories/<?= (int) $category['id'] ?>"><?= csrf_field() ?></form>
                <form id="<?= $fid ?>-delete" method="post" action="/admin/community/categories/<?= (int) $category['id'] ?>/delete" data-confirm="<?= e(t('Deleting a category also removes its boards and topics.')) ?>"><?= csrf_field() ?></form>
            <?php endforeach; ?>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead><tr><th><?= e(t('Name')) ?></th><th><?= e(t('Description')) ?></th><th><?= e(t('Position')) ?></th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($categories as $category): ?>
                        <?php $fid = 'category-' . (int) $category['id']; ?>
                        <tr>
                            <td><input form="<?= $fid ?>" name="name" value="<?= e($category['name']) ?>" required></td>
                            <td><input form="<?= $fid ?>" name="description" value="<?= e($category['description']) ?>"></td>
                            <td><input form="<?= $fid ?>" type="number" name="position" value="<?= (int) $category['position'] ?>"></td>
                            <td class="table-actions">
                                <button form="<?= $fid ?>" class="btn-mini" type="submit"><?= e(t('Save')) ?></button>
                                <button form="<?= $fid ?>-delete" class="btn-mini danger" type="submit"><?= e(t('Delete')) ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <form class="inline-create" method="post" action="/admin/community/categories">
                <?= csrf_field() ?>
                <input name="name" placeholder="<?= e(t('New category name')) ?>" required>
                <input name="description" placeholder="<?= e(t('Description')) ?>">
                <input type="number" name="position" placeholder="<?= e(t('Position')) ?>" value="0">
                <button class="btn btn-primary" type="submit">+ <?= e(t('Add category')) ?></button>
            </form>
        </section>

        <section class="section-block compact-section">
            <div class="section-heading">
                <div><span class="section-code">BOARDS</span><h2><?= e(t('Boards')) ?></h2></div>
                <span class="section-count"><?= count($boards) ?></span>
            </div>
            <?php foreach ($boards as $board): ?>
                <?php $fid = 'board-' . (int) $board['id']; ?>
                <form id="<?= $fid ?>" method="post" action="/admin/community/boards/<?= (int) $board['id'] ?>"><?= csrf_field() ?></form>
                <form id="<?= $fid ?>-delete" method="post" action="/admin/community/boards/<?= (int) $board['id'] ?>/delete" data-confirm="<?= e(t('Deleting a board also removes its topics.')) ?>"><?= csrf_field() ?></form>
            <?php endforeach; ?>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead><tr><th><?= e(t('Category')) ?></th><th><?= e(t('Name')) ?></th><th><?= e(t('Description')) ?></th><th><?= e(t('Topics')) ?></th><th><?= e(t('Position')) ?></th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($boards as $board): ?>
                        <?php $fid = 'board-' . (int) $board['id']; ?>
                        <tr>
                            <td>
                                <select form="<?= $fid ?>" name="category_id" required>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= (int) $category['id'] ?>" <?= (int) $category['id'] === (int) $board['category_id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input form="<?= $fid ?>" name="name" value="<?= e($board['name']) ?>" required></td>
                            <td><input form="<?= $fid ?>" name="description" value="<?= e($board['description']) ?>"></td>
                            <td><?= (int) $board['topic_count'] ?></td>
                            <td><input form="<?= $fid ?>" type="number" name="position" value="<?= (int) $board['position'] ?>"></td>
                            <td class="table-actions">
                                <button form="<?= $fid ?>" class="btn-mini" type="submit"><?= e(t('Save')) ?></button>
                                <button form="<?= $fid ?>-delete" class="btn-mini danger" type="submit"><?= e(t('Delete')) ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <form class="inline-create" method="post" action="/admin/community/boards">
                <?= csrf_field() ?>
                <select name="category_id" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input name="name" placeholder="<?= e(t('New board name')) ?>" required>
                <input name="description" placeholder="<?= e(t('Description')) ?>">
                <input type="number" name="position" placeholder="<?= e(t('Position')) ?>" value="0">
                <button class="btn btn-primary" type="submit">+ <?= e(t('Add board')) ?></button>
            </form>
        </section>
    </div>
</div>
