<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repository-body">
    <div class="repo-toolbar">
        <form class="branch-control" method="get" action="<?= e(repo_url($repository)) ?>">
            <?php if ($path !== ''): ?><input type="hidden" name="path" value="<?= e($path) ?>"><?php endif; ?>
            <span class="section-code">BRANCH</span>
            <select name="ref" data-auto-submit><?php foreach ($branches as $item): ?><option value="<?= e($item) ?>" <?= $item === $branch ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select>
        </form>
        <div class="breadcrumbs">
            <a href="<?= e(repo_url($repository, '?' . http_build_query(['ref' => $branch]))) ?>"><?= e($repository['name']) ?></a>
            <?php $segments = $path === '' ? [] : explode('/', $path); $cumulative = []; ?>
            <?php foreach ($segments as $segment): ?><?php $cumulative[] = $segment; ?><span>/</span><a href="<?= e(repo_url($repository, '?' . http_build_query(['ref' => $branch, 'path' => implode('/', $cumulative)]))) ?>"><?= e($segment) ?></a><?php endforeach; ?>
        </div>
    </div>
    <div class="clone-box"><span>HTTPS / CLONE</span><input id="clone-url" type="text" readonly value="<?= e(app_url($repository['username'] . '/' . $repository['name'] . '.git')) ?>"><button type="button" data-copy="#clone-url" data-copied="<?= t('Copied') ?>"><?= t('Copy') ?></button></div>
    <section class="file-browser">
        <header class="file-browser-header"><strong><?= $latest ? e($latest['subject']) : t('No commits yet') ?></strong><span><?= $latest ? e(short_sha($latest['sha'])) . ' · ' . e(format_date($latest['author_date'])) : '' ?></span></header>
        <?php if ($path !== ''): ?><?php $parent = dirname($path); ?><a class="file-row" href="<?= e(repo_url($repository, '?' . http_build_query(['ref' => $branch, 'path' => $parent === '.' ? '' : $parent]))) ?>"><span class="file-type">DIR</span><span class="file-name">..</span><span class="file-commit"></span><span class="file-date"></span></a><?php endif; ?>
        <?php foreach ($entries as $entry): ?>
            <?php $isDirectory = $entry['type'] === 'tree'; $target = $isDirectory ? repo_url($repository, '?' . http_build_query(['ref' => $branch, 'path' => $entry['path']])) : repo_url($repository, 'blob?' . http_build_query(['ref' => $branch, 'path' => $entry['path']])); ?>
            <a class="file-row" href="<?= e($target) ?>"><span class="file-type"><?= $isDirectory ? 'DIR' : 'FILE' ?></span><span class="file-name"><?= e($entry['name']) ?></span><span class="file-commit"><?= $isDirectory ? t('Directory') : ($entry['size'] !== null ? number_format($entry['size']) . ' B' : '') ?></span><span class="file-date"><?= e(short_sha($entry['sha'])) ?></span></a>
        <?php endforeach; ?>
        <?php if (!$entries && $path === ''): ?><div class="empty-state"><p><?= t('This repository has no files.') ?></p></div><?php endif; ?>
    </section>
    <?php if ($readme !== null): ?><section class="readme-panel"><div class="readme-title">README.md / PROJECT DOCUMENT</div><div class="readme-content markdown-body"><?= render_markdown($readme) ?></div></section><?php endif; ?>
</div>
