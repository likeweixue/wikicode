<div class="admin-layout">
    <?php require __DIR__ . '/_nav.php'; ?>
    <div>
        <section class="page-heading">
            <div>
                <span class="section-code">ACCESS / IDENTITIES</span>
                <h1><?= e(t('User management')) ?></h1>
                <p><?= e(t('Manage account access, administrator roles and owned repositories.')) ?></p>
            </div>
        </section>

        <div class="data-table-wrap">
            <table class="data-table">
                <thead><tr><th><?= e(t('User')) ?></th><th><?= e(t('Email address')) ?></th><th><?= e(t('Role')) ?></th><th><?= e(t('Repositories')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Joined')) ?></th><th><?= e(t('Actions')) ?></th></tr></thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?= e($user['username']) ?></strong><small>ID / <?= e($user['id']) ?></small></td>
                        <td><?= e($user['email']) ?></td>
                        <td><span class="badge"><?= e($user['role'] === 'admin' ? t('Administrator') : t('User')) ?></span></td>
                        <td><?= e($user['repository_count']) ?></td>
                        <td><span class="badge <?= (int) $user['is_active'] === 1 ? 'badge-open' : 'badge-closed' ?>"><?= e((int) $user['is_active'] === 1 ? t('Active') : t('Disabled')) ?></span></td>
                        <td><?= e(format_date($user['created_at'])) ?></td>
                        <td>
                            <?php if ((int) $user['id'] === (int) $currentUser['id']): ?>
                                <span class="muted"><?= e(t('Current account')) ?></span>
                            <?php else: ?>
                                <div class="table-actions">
                                    <form method="post" action="/admin/users/<?= e($user['id']) ?>/toggle">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-small btn-ghost" type="submit"><?= e((int) $user['is_active'] === 1 ? t('Disable') : t('Enable')) ?></button>
                                    </form>
                                    <form method="post" action="/admin/users/<?= e($user['id']) ?>/role">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-small btn-ghost" type="submit"><?= e($user['role'] === 'admin' ? t('Make user') : t('Make administrator')) ?></button>
                                    </form>
                                    <form method="post" action="/admin/users/<?= e($user['id']) ?>/delete" data-confirm="<?= e(t('Delete this user and all associated repositories? This cannot be undone.')) ?>">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-small btn-danger" type="submit"><?= e(t('Delete')) ?></button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
