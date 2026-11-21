<?php
// 仓库操作按钮数据（Watch / Fork / Star）。所有引用此组件的页面共享同一份数据。
$__rh = \App\Services\RepositoryService::headerStats((int) $repository['id']);
?>
<header class="repository-header">
    <div class="repository-heading">
        <div class="repo-heading-left">
            <span class="section-code">REPOSITORY / <?= e(str_pad((string) $repository['id'], 5, '0', STR_PAD_LEFT)) ?> / <?= strtoupper($repository['visibility']) ?></span>
            <h1><a href="/<?= rawurlencode($repository['username']) ?>"><?= e($repository['username']) ?></a><span>/</span><a href="<?= e(repo_url($repository)) ?>"><?= e($repository['name']) ?></a><span class="badge <?= $repository['visibility'] === 'private' ? 'badge-private' : '' ?>"><?= t(ucfirst($repository['visibility'])) ?></span></h1>
            <p><?= e($repository['description'] ?: t('No description provided.')) ?></p>
        </div>
        <ul class="repo-actions">
            <li>
                <form method="post" action="<?= e(repo_url($repository, 'watch')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="repo-action-btn <?= $__rh['isWatched'] ? 'is-active' : '' ?>" title="<?= $__rh['isWatched'] ? '取消关注此仓库' : '关注此仓库，接收动态通知' ?>">
                        <?= \App\Core\Icons::svg('eye') ?>
                        <span class="ra-label"><?= $__rh['isWatched'] ? '已关注' : '关注' ?></span>
                        <span class="ra-count"><?= (int) $__rh['watchCount'] ?></span>
                    </button>
                </form>
            </li>
            <li>
                <form method="post" action="<?= e(repo_url($repository, 'fork')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="repo-action-btn" title="复刻此仓库到你的账号下">
                        <?= \App\Core\Icons::svg('fork') ?>
                        <span class="ra-label">复刻</span>
                        <span class="ra-count"><?= (int) $__rh['forkCount'] ?></span>
                    </button>
                </form>
            </li>
            <li>
                <form method="post" action="<?= e(repo_url($repository, 'star')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="repo-action-btn <?= $__rh['isStarred'] ? 'is-active' : '' ?>" title="<?= $__rh['isStarred'] ? '取消星标' : '给此仓库加星标' ?>">
                        <?= \App\Core\Icons::svg($__rh['isStarred'] ? 'star-filled' : 'star') ?>
                        <span class="ra-label"><?= $__rh['isStarred'] ? '已星标' : '星标' ?></span>
                        <span class="ra-count"><?= (int) $__rh['starCount'] ?></span>
                    </button>
                </form>
            </li>
        </ul>
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
