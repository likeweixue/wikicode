<header class="repository-header">
    <div class="repository-heading">
        <div>
            <span class="section-code">REPOSITORY / <?= e(str_pad((string) $repository['id'], 5, '0', STR_PAD_LEFT)) ?> / <?= strtoupper($repository['visibility']) ?></span>
            <h1><a href="/<?= rawurlencode($repository['username']) ?>"><?= e($repository['username']) ?></a><span>/</span><a href="<?= e(repo_url($repository)) ?>"><?= e($repository['name']) ?></a><span class="badge <?= $repository['visibility'] === 'private' ? 'badge-private' : '' ?>"><?= t(ucfirst($repository['visibility'])) ?></span></h1>
            <p><?= e($repository['description'] ?: t('No description provided.')) ?></p>
        </div>
    </div>
</header>
<nav class="repository-nav">
    <a class="<?= active_class(($activeTab ?? '') === 'code') ?>" href="<?= e(repo_url($repository)) ?>"><?= t('Code') ?></a>
    <a class="<?= active_class(($activeTab ?? '') === 'issues') ?>" href="<?= e(repo_url($repository, 'issues')) ?>"><?= t('Issues') ?><?php if (isset($repository['issue_count'])): ?><span><?= e($repository['issue_count']) ?></span><?php endif; ?></a>
    <a class="<?= active_class(($activeTab ?? '') === 'pulls') ?>" href="<?= e(repo_url($repository, 'pulls')) ?>"><?= t('Pull requests') ?><?php if (isset($repository['pull_count'])): ?><span><?= e($repository['pull_count']) ?></span><?php endif; ?></a>
    <a class="<?= active_class(($activeTab ?? '') === 'wiki') ?>" href="<?= e(repo_url($repository, 'wiki')) ?>"><?= t('Wiki') ?></a>
    <a class="<?= active_class(($activeTab ?? '') === 'insights') ?>" href="<?= e(repo_url($repository, 'insights')) ?>"><?= t('Insights') ?></a>
    <a class="<?= active_class(($activeTab ?? '') === 'history') ?>" href="<?= e(repo_url($repository, 'commits')) ?>"><?= t('History') ?></a>
    <?php if (\App\Core\Auth::canWrite($repository)): ?><a class="<?= active_class(($activeTab ?? '') === 'settings') ?>" href="<?= e(repo_url($repository, 'settings')) ?>"><?= t('Settings') ?></a><?php endif; ?>
</nav>
