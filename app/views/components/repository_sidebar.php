<?php
$sc = $sidebar;
$colors = $sc['languageColors'] ?? [];
$totalStars = $sc['starCount'] ?? 0;
$totalWatches = $sc['watchCount'] ?? 0;
$totalForks = $sc['forkCount'] ?? 0;
?>
<div class="sidebar-section">
    <div class="sidebar-title"><?= t('About') ?></div>
    <p class="sidebar-desc"><?= e($repository['description'] ?: t('No description provided.')) ?></p>
    <?php if (!empty($repository['homepage'])): ?>
        <a class="sidebar-link" href="<?= e($repository['homepage']) ?>" target="_blank" rel="noopener noreferrer"><?= e($repository['homepage']) ?> ↗</a>
    <?php endif; ?>
    <div class="sidebar-stats">
        <div class="stat-item">
            <form method="post" action="<?= e(repo_url($repository, 'star')) ?>" class="inline-form">
                <?= csrf_field() ?>
                <button class="stat-btn <?= $sc['isStarred'] ? 'is-active' : '' ?>" type="submit">★ <b><?= $totalStars ?></b></button>
            </form>
            <span class="stat-label"><?= ngettext('Star', 'Stars', $totalStars) ?></span>
        </div>
        <div class="stat-item">
            <form method="post" action="<?= e(repo_url($repository, 'watch')) ?>" class="inline-form">
                <?= csrf_field() ?>
                <button class="stat-btn <?= $sc['isWatched'] ? 'is-active' : '' ?>" type="submit">👁 <b><?= $totalWatches ?></b></button>
            </form>
            <span class="stat-label"><?= ngettext('Watcher', 'Watchers', $totalWatches) ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-btn stat-static">⑂ <b><?= $totalForks ?></b></span>
            <span class="stat-label"><?= ngettext('Fork', 'Forks', $totalForks) ?></span>
        </div>
    </div>
</div>

<?php if (!empty($sc['licenseName'])): ?>
<div class="sidebar-section">
    <div class="sidebar-title"><?= t('License') ?></div>
    <div class="sidebar-badge-wrap">
        <span class="license-badge"><?= e($sc['licenseName']) ?></span>
        <?php if (!empty($sc['licenseUrl'])): ?><a class="sidebar-link small" href="<?= e($sc['licenseUrl']) ?>" target="_blank" rel="noopener noreferrer">↗</a><?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php $sidebarTags = $tags ?? []; ?>
<div class="sidebar-section">
    <div class="sidebar-title"><?= t('Releases') ?></div>
    <?php if (!$sidebarTags): ?>
        <p class="sidebar-empty"><?= t('No releases published') ?></p>
    <?php else: ?>
        <ul class="release-list">
            <?php foreach (array_slice($sidebarTags, 0, 4) as $sidebarTag): ?>
                <li><a href="<?= e(repo_url($repository, '?' . http_build_query(['ref' => $sidebarTag]))) ?>"><span class="release-tag">🏷 <?= e($sidebarTag) ?></span><span class="release-link"><?= t('Latest') ?> ↗</span></a></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php if (!empty($sc['languages'])): ?>
<div class="sidebar-section">
    <div class="sidebar-title"><?= t('Languages') ?></div>
    <div class="lang-bar">
        <?php foreach ($sc['languages'] as $lang => $pct): $color = $colors[$lang] ?? '#999'; ?>
            <span class="lang-segment" style="background: <?= $color ?>; width: <?= min($pct, 100) ?>%" title="<?= e($lang) . ' ' . $pct . '%' ?>"></span>
        <?php endforeach; ?>
    </div>
    <ul class="lang-list">
        <?php foreach ($sc['languages'] as $lang => $pct): $color = $colors[$lang] ?? '#999'; ?>
            <li><span class="lang-dot" style="background: <?= $color ?>"></span><b><?= e($lang) ?></b> <span><?= $pct ?>%</span></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="sidebar-section sidebar-meta">
    <span class="section-code">META / ARCHIVE</span>
    <p><b><?= t('Created') ?>:</b> <?= e(format_date($repository['created_at'])) ?></p>
    <p><b><?= t('Updated') ?>:</b> <?= e(format_date($repository['updated_at'])) ?></p>
</div>
