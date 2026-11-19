<?php
$activeSlug = $currentCategorySlug ?? '';
?>
<section class="page-heading explore-heading">
    <div>
        <span class="section-code">PUBLIC ARCHIVE / EXPLORE</span>
        <h1><?= t('Explore') ?></h1>
        <p><?= t('Browse repositories by category, newest first.') ?></p>
    </div>
</section>

<div class="explore-grid">
    <!-- 左栏：分类 -->
    <aside class="explore-categories">
        <div class="explore-panel-head"><span><?= t('Categories') ?></span></div>
        <nav class="category-nav" aria-label="<?= e(t('Categories')) ?>">
            <a class="category-link <?= $activeSlug === '' ? 'is-active' : '' ?>" href="/explore">
                <span class="category-icon"><?= \App\Core\Icons::svg('all') ?></span>
                <span class="category-name"><?= t('All') ?></span>
                <span class="category-count"><?= (int) ($totalPublic ?? count($repositories)) ?></span>
            </a>
            <?php foreach ($categories as $cat): ?>
                <?php $isActive = $activeSlug === ($cat['slug'] ?? ''); ?>
                <a class="category-link <?= $isActive ? 'is-active' : '' ?>" href="/explore?category=<?= e(rawurlencode((string) $cat['slug'])) ?>">
                    <span class="category-icon"><?= \App\Services\CategoryService::iconSvg((string) ($cat['icon'] ?? '')) ?></span>
                    <span class="category-name"><?= e((string) $cat['name']) ?></span>
                    <span class="category-count"><?= (int) ($cat['repo_count'] ?? 0) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <!-- 中栏：仓库列表 -->
    <section class="explore-repos">
        <?php $showOwner = true; require __DIR__ . '/components/repository_list.php'; ?>
    </section>

    <!-- 右栏：热门 / 推荐 -->
    <aside class="explore-hot">
        <div class="explore-panel-head"><span><?= e($hotTitle) ?></span><?php if ($hotMode === 'featured'): ?><small><?= t('Featured') ?></small><?php else: ?><small>★</small><?php endif; ?></div>
        <?php if (!$hotRepos): ?>
            <p class="hot-empty"><?= t('Nothing here yet.') ?></p>
        <?php else: ?>
            <ol class="hot-list">
                <?php foreach ($hotRepos as $i => $repo): ?>
                    <li class="hot-item">
                        <span class="hot-rank"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <div class="hot-main">
                            <a class="hot-name" href="<?= e(repo_url($repo)) ?>"><?= e($repo['username']) ?>/<?= e($repo['name']) ?></a>
                            <?php if (!empty($repo['description'])): ?><p><?= e($repo['description']) ?></p><?php endif; ?>
                        </div>
                        <span class="hot-stars" title="<?= t('Stars') ?>">★ <?= (int) ($repo['star_count'] ?? 0) ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </aside>
</div>
