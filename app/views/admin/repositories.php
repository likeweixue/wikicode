<div class="admin-layout">
    <?php require __DIR__ . '/_nav.php'; ?>
    <div>
        <section class="page-heading">
            <div>
                <span class="section-code">ARCHIVES / ALL OWNERS</span>
                <h1><?= e(t('Repository management')) ?></h1>
                <p><?= e(t('Inspect every public and private repository hosted on this instance.')) ?></p>
            </div>
        </section>

        <?php if (!$repositories): ?>
            <div class="empty-state"><span class="empty-cross" aria-hidden="true">+</span><h3><?= e(t('No repositories yet')) ?></h3></div>
        <?php else: ?>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead><tr><th><?= e(t('Repository')) ?></th><th><?= e(t('Owner')) ?></th><th><?= e(t('Visibility')) ?></th><th><?= e(t('Open issues')) ?></th><th><?= e(t('Open pull requests')) ?></th><th><?= e(t('Updated')) ?></th><th><?= e(t('Actions')) ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($repositories as $repository): ?>
                        <tr>
                            <td><a class="repository-name" href="<?= e(repo_url($repository)) ?>"><?= e($repository['name']) ?></a><small><?= e($repository['description']) ?></small></td>
                            <td><a href="/<?= e(rawurlencode($repository['username'])) ?>"><?= e($repository['username']) ?></a></td>
                            <td><span class="badge <?= $repository['visibility'] === 'private' ? 'badge-private' : '' ?>"><?= e($repository['visibility'] === 'private' ? t('Private') : t('Public')) ?></span></td>
                            <td><?= e($repository['open_issues']) ?></td>
                            <td><?= e($repository['open_pulls']) ?></td>
                            <td><?= e(format_date($repository['updated_at'])) ?></td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-small btn-ghost" href="<?= e(repo_url($repository)) ?>"><?= e(t('View')) ?></a>
                                    <form method="post" action="/admin/repositories/<?= e($repository['id']) ?>/delete" data-confirm="<?= e(t('Delete this repository and all of its data? This cannot be undone.')) ?>">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-small btn-danger" type="submit"><?= e(t('Delete')) ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
