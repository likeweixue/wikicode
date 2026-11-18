<section class="page-heading dashboard-heading">
    <div>
        <span class="section-code">CONTRIBUTOR CONSOLE / <?= e(str_pad((string) $currentUser['id'], 4, '0', STR_PAD_LEFT)) ?></span>
        <h1><?= t('Good to see you, :name.', ['name' => $currentUser['username']]) ?></h1>
        <p><?= t('Your repositories and active collaboration channels are ready.') ?></p>
    </div>
    <a class="btn btn-primary" href="/new">+ <?= t('New repository') ?></a>
</section>

<nav class="dashboard-meta-nav">
    <div class="meta-item"><span>REPOSITORIES</span><strong><?= str_pad((string) $stats['repositories'], 2, '0', STR_PAD_LEFT) ?></strong></div>
    <div class="meta-item"><span>OPEN ISSUES</span><strong><?= str_pad((string) $stats['issues'], 2, '0', STR_PAD_LEFT) ?></strong></div>
    <div class="meta-item"><span>PULL REQUESTS</span><strong><?= str_pad((string) $stats['pulls'], 2, '0', STR_PAD_LEFT) ?></strong></div>
    <div class="meta-item meta-status"><span>SYSTEM</span><strong><i class="status-dot"></i> ONLINE</strong></div>
</nav>

<section class="section-block">
    <div class="section-heading">
        <div><span class="section-code">PERSONAL ARCHIVE / INDEX</span><h2><?= $view === 'starred' ? t('My starred') : t('Your repositories') ?></h2></div>
        <div class="view-tabs">
            <a class="view-tab <?= $view === 'repositories' ? 'is-active' : '' ?>" href="/dashboard"><?= t('Your repositories') ?></a>
            <a class="view-tab <?= $view === 'starred' ? 'is-active' : '' ?>" href="/dashboard?view=starred">★ <?= t('My starred') ?> <span class="view-tab-count"><?= count($starredRepositories) ?></span></a>
        </div>
    </div>
    <?php if ($view === 'starred'): ?>
        <?php $repositories = $starredRepositories; $showOwner = true; require __DIR__ . '/components/repository_list.php'; ?>
    <?php else: ?>
        <?php $showOwner = false; require __DIR__ . '/components/repository_list.php'; ?>
    <?php endif; ?>
</section>

<?php if ($publicRepositories): ?>
<section class="section-block compact-section">
    <div class="section-heading"><div><span class="section-code">NETWORK DISCOVERIES</span><h2><?= t('From the community') ?></h2></div></div>
    <?php $repositories = $publicRepositories; $showOwner = true; require __DIR__ . '/components/repository_list.php'; ?>
</section>
<?php endif; ?>
