<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repo-layout repo-layout-wiki">
    <aside class="wiki-sidebar">
        <div class="wiki-sidebar-header">
            <span class="section-code">WIKI / PAGES</span>
            <?php if (\App\Core\Auth::canWrite($repository)): ?>
                <a class="btn btn-small btn-primary" href="<?= e(repo_url($repository, 'wiki/_new')) ?>"><?= t('New page') ?> +</a>
            <?php endif; ?>
        </div>
        <?php if (empty($pages)): ?>
            <div class="wiki-empty"><?= t('No wiki pages yet.') ?></div>
        <?php else: ?>
            <ul class="wiki-tree">
                <?php foreach ($pages as $p): ?>
                    <li class="<?= ($page['slug'] ?? '') === $p['slug'] ? 'is-current' : '' ?>">
                        <a href="<?= e(repo_url($repository, 'wiki/' . rawurlencode($p['slug']))) ?>"><?= e($p['title']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </aside>
    <div class="repo-main repo-main-full">
        <?php if ($page): ?>
            <article class="wiki-article">
                <header class="wiki-article-header">
                    <h2><?= e($page['title']) ?></h2>
                    <?php if (\App\Core\Auth::canWrite($repository)): ?>
                        <div class="wiki-article-actions">
                            <a class="btn btn-small" href="<?= e(repo_url($repository, 'wiki/' . rawurlencode($page['slug']) . '/_edit')) ?>"><?= t('Edit') ?></a>
                            <form method="post" action="<?= e(repo_url($repository, 'wiki/' . rawurlencode($page['slug']) . '/_delete')) ?>" onsubmit="return confirm('<?= t('Delete this wiki page?') ?>');">
                                <?= csrf_field() ?>
                                <button class="btn btn-small btn-danger" type="submit"><?= t('Delete') ?></button>
                            </form>
                        </div>
                    <?php endif; ?>
                </header>
                <div class="wiki-content markdown-body"><?= render_markdown($page['body']) ?></div>
            </article>
        <?php else: ?>
            <div class="wiki-empty-page">
                <p><?= t('This wiki has no pages yet.') ?></p>
                <?php if (\App\Core\Auth::canWrite($repository)): ?>
                    <a class="btn btn-primary" href="<?= e(repo_url($repository, 'wiki/_new')) ?>"><?= t('Create the first page') ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
