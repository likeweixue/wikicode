<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repository-body">
    <div class="repo-toolbar"><div class="breadcrumbs"><a href="<?= e(repo_url($repository, '?' . http_build_query(['ref' => $branch]))) ?>"><?= e($repository['name']) ?></a><?php $segments = explode('/', $path); $cumulative = []; ?><?php foreach ($segments as $index => $segment): ?><?php $cumulative[] = $segment; ?><span>/</span><?php if ($index < count($segments) - 1): ?><a href="<?= e(repo_url($repository, '?' . http_build_query(['ref' => $branch, 'path' => implode('/', $cumulative)]))) ?>"><?= e($segment) ?></a><?php else: ?><span><?= e($segment) ?></span><?php endif; ?><?php endforeach; ?></div><span class="badge"><?= e($branch) ?></span></div>
    <section class="code-panel">
        <header class="code-panel-header"><span><?= e(number_format($file['size'])) ?> B / <?= $binary ? 'BINARY' : 'UTF-8 TEXT' ?></span><a href="<?= e(repo_url($repository, 'raw?' . http_build_query(['ref' => $branch, 'path' => $path]))) ?>"><?= t('Raw file') ?> ↗</a></header>
        <?php if ($binary): ?><div class="empty-state"><h3><?= t('Binary file') ?></h3><p><?= t('Binary content cannot be previewed safely in the browser.') ?></p></div><?php else: ?><pre class="code-content"><code><?= e($file['content']) ?></code></pre><?php endif; ?>
    </section>
</div>
