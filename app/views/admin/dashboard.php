<div class="admin-layout">
    <?php require __DIR__ . '/_nav.php'; ?>
    <div>
        <section class="page-heading">
            <div>
                <span class="section-code">SYSTEM / OVERVIEW</span>
                <h1><?= e(t('Administration')) ?></h1>
                <p><?= e(t('Monitor accounts, repositories and collaboration across this instance.')) ?></p>
            </div>
        </section>

        <section class="metric-grid">
            <article><span><?= e(t('Users')) ?></span><strong><?= e(str_pad((string) $stats['users'], 2, '0', STR_PAD_LEFT)) ?></strong><small><?= e(t('Registered accounts')) ?></small></article>
            <article><span><?= e(t('Repositories')) ?></span><strong><?= e(str_pad((string) $stats['repositories'], 2, '0', STR_PAD_LEFT)) ?></strong><small><?= e(t('Hosted archives')) ?></small></article>
            <article><span><?= e(t('Open issues')) ?></span><strong><?= e(str_pad((string) $stats['open_issues'], 2, '0', STR_PAD_LEFT)) ?></strong><small><?= e(t('Awaiting discussion')) ?></small></article>
            <article><span><?= e(t('Open pull requests')) ?></span><strong><?= e(str_pad((string) $stats['open_pulls'], 2, '0', STR_PAD_LEFT)) ?></strong><small><?= e(t('Awaiting review')) ?></small></article>
        </section>

        <section class="section-block compact-section">
            <div class="section-heading">
                <div><span class="section-code">RECENT / REPOSITORIES</span><h2><?= e(t('Recently updated repositories')) ?></h2></div>
                <a class="text-link" href="/admin/repositories"><?= e(t('Manage all')) ?> ↗</a>
            </div>
            <?php if (!$repositories): ?>
                <div class="empty-state"><span class="empty-cross" aria-hidden="true">+</span><h3><?= e(t('No repositories yet')) ?></h3></div>
            <?php else: ?>
                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead><tr><th><?= e(t('Repository')) ?></th><th><?= e(t('Owner')) ?></th><th><?= e(t('Visibility')) ?></th><th><?= e(t('Default branch')) ?></th><th><?= e(t('Updated')) ?></th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($repositories as $repository): ?>
                            <tr>
                                <td><a class="repository-name" href="<?= e(repo_url($repository)) ?>"><?= e($repository['name']) ?></a><small><?= e($repository['description']) ?></small></td>
                                <td><a href="/<?= e(rawurlencode($repository['username'])) ?>"><?= e($repository['username']) ?></a></td>
                                <td><span class="badge <?= $repository['visibility'] === 'private' ? 'badge-private' : '' ?>"><?= e($repository['visibility'] === 'private' ? t('Private') : t('Public')) ?></span></td>
                                <td class="mono"><?= e($repository['default_branch']) ?></td>
                                <td><?= e(format_date($repository['updated_at'])) ?></td>
                                <td><a class="text-link" href="<?= e(repo_url($repository)) ?>" aria-label="<?= e(t('Open repository')) ?>">↗</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
