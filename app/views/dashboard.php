<section class="page-heading dashboard-heading">
    <div>
        <span class="section-code">CONTRIBUTOR CONSOLE / <?= e(str_pad((string) $currentUser['id'], 4, '0', STR_PAD_LEFT)) ?></span>
        <h1><?= t('Good to see you, :name.', ['name' => $currentUser['username']]) ?></h1>
        <p><?= t('Your repositories and active collaboration channels are ready.') ?></p>
    </div>
    <a class="btn btn-primary" href="/new">+ <?= t('New repository') ?></a>
</section>

<section class="metric-grid">
    <article><span>REPOSITORIES</span><strong><?= str_pad((string) $stats['repositories'], 2, '0', STR_PAD_LEFT) ?></strong><small><?= t('Owned archives') ?></small></article>
    <article><span>OPEN ISSUES</span><strong><?= str_pad((string) $stats['issues'], 2, '0', STR_PAD_LEFT) ?></strong><small><?= t('Awaiting discussion') ?></small></article>
    <article><span>PULL REQUESTS</span><strong><?= str_pad((string) $stats['pulls'], 2, '0', STR_PAD_LEFT) ?></strong><small><?= t('Awaiting review') ?></small></article>
    <article class="metric-status"><span>SYSTEM STATUS</span><strong><i class="status-dot"></i> ONLINE</strong><small>GIT SERVICES AVAILABLE</small></article>
</section>

<section class="section-block">
    <div class="section-heading"><div><span class="section-code">PERSONAL ARCHIVE / INDEX</span><h2><?= t('Your repositories') ?></h2></div><a class="text-link" href="/new"><?= t('Initialize new') ?> ↗</a></div>
    <?php $showOwner = false; require __DIR__ . '/components/repository_list.php'; ?>
</section>

<?php if ($publicRepositories): ?>
<section class="section-block compact-section">
    <div class="section-heading"><div><span class="section-code">NETWORK DISCOVERIES</span><h2><?= t('From the community') ?></h2></div></div>
    <?php $repositories = $publicRepositories; $showOwner = true; require __DIR__ . '/components/repository_list.php'; ?>
</section>
<?php endif; ?>
