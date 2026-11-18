<?php require __DIR__ . '/../components/repository_header.php'; ?>
<?php
$tags = $tags ?? [];
$cloneUrl = app_url($repository['username'] . '/' . $repository['name'] . '.git');
$sshHost = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
$sshUrl = 'git@' . $sshHost . ':' . $repository['username'] . '/' . $repository['name'] . '.git';
$refQuery = ['ref' => $branch];
if ($path !== '') { $refQuery['path'] = $path; }
?>
<div class="repo-layout">
    <div class="repo-main">
        <div class="repo-toolbar-new">
            <div class="toolbar-left">
                <details class="dropdown">
                    <summary class="tool-btn"><span class="tool-ico">⑂</span> <?= e($branch) ?> <span class="caret">▾</span></summary>
                    <div class="dropdown-menu">
                        <div class="dropdown-head"><?= t('Branches') ?></div>
                        <?php foreach ($branches as $item): ?>
                            <a class="dropdown-item <?= $item === $branch ? 'is-current' : '' ?>" href="<?= e(repo_url($repository, '?' . http_build_query(['ref' => $item] + ($path !== '' ? ['path' => $path] : [])))) ?>"><?= e($item) ?></a>
                        <?php endforeach; ?>
                    </div>
                </details>
                <details class="dropdown">
                    <summary class="tool-btn"><span class="tool-ico">🏷</span> <?= t('Tags') ?> <span class="caret">▾</span></summary>
                    <div class="dropdown-menu">
                        <div class="dropdown-head"><?= t('Tags') ?></div>
                        <?php if (!$tags): ?>
                            <div class="dropdown-empty"><?= t('No tags') ?></div>
                        <?php endif; ?>
                        <?php foreach ($tags as $tag): ?>
                            <a class="dropdown-item" href="<?= e(repo_url($repository, '?' . http_build_query(['ref' => $tag]))) ?>"><?= e($tag) ?></a>
                        <?php endforeach; ?>
                    </div>
                </details>
                <?php if (\App\Core\Auth::canWrite($repository)): ?>
                    <a class="tool-btn" href="<?= e(repo_url($repository, 'new-file?' . http_build_query($refQuery))) ?>"><span class="tool-ico">＋</span> <?= t('Add file') ?></a>
                <?php endif; ?>
            </div>
            <div class="toolbar-right">
                <details class="dropdown dropdown-right">
                    <summary class="tool-btn btn-code"><span class="tool-ico">&lt;/&gt;</span> <?= t('Code') ?> <span class="caret">▾</span></summary>
                    <div class="dropdown-menu code-menu">
                        <div class="dropdown-head"><?= t('Clone') ?></div>
                        <div class="code-field">
                            <label>HTTPS</label>
                            <div class="code-field-row">
                                <input id="clone-https" type="text" readonly value="<?= e($cloneUrl) ?>">
                                <button type="button" data-copy="#clone-https" data-copied="<?= t('Copied') ?>"><?= t('Copy') ?></button>
                            </div>
                        </div>
                        <div class="code-field">
                            <label>SSH</label>
                            <div class="code-field-row">
                                <input id="clone-ssh" type="text" readonly value="<?= e($sshUrl) ?>">
                                <button type="button" data-copy="#clone-ssh" data-copied="<?= t('Copied') ?>"><?= t('Copy') ?></button>
                            </div>
                        </div>
                        <a class="dropdown-item code-download" href="<?= e(repo_url($repository, 'archive?' . http_build_query(['ref' => $branch]))) ?>">⇩ <?= t('Download ZIP') ?></a>
                    </div>
                </details>
            </div>
        </div>
        <div class="repo-toolbar">
            <div class="breadcrumbs">
                <a href="<?= e(repo_url($repository, '?' . http_build_query(['ref' => $branch]))) ?>"><?= e($repository['name']) ?></a>
                <?php $segments = $path === '' ? [] : explode('/', $path); $cumulative = []; ?>
                <?php foreach ($segments as $segment): ?><?php $cumulative[] = $segment; ?><span>/</span><a href="<?= e(repo_url($repository, '?' . http_build_query(['ref' => $branch, 'path' => implode('/', $cumulative)]))) ?>"><?= e($segment) ?></a><?php endforeach; ?>
            </div>
        </div>
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
    <?php if (!empty($sidebar)): ?>
    <aside class="repo-sidebar">
        <?php require __DIR__ . '/../components/repository_sidebar.php'; ?>
    </aside>
    <?php endif; ?>
</div>
