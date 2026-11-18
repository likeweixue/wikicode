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
            </div>
            <div class="toolbar-right">
                <form class="repo-search" method="get" action="<?= e(repo_url($repository)) ?>">
                    <input type="hidden" name="ref" value="<?= e($branch) ?>">
                    <span class="repo-search-ico" aria-hidden="true">⌕</span>
                    <input type="search" name="q" value="<?= e($search ?? '') ?>" placeholder="<?= t('Search files...') ?>" aria-label="<?= t('Search files') ?>">
                </form>
                <?php if (\App\Core\Auth::canWrite($repository)): ?>
                    <details class="dropdown dropdown-right">
                        <summary class="tool-btn"><span class="tool-ico">＋</span> <?= t('Add file') ?> <span class="caret">▾</span></summary>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="<?= e(repo_url($repository, 'new-file?' . http_build_query($refQuery))) ?>">⬆ <?= t('Upload file') ?></a>
                            <a class="dropdown-item" href="<?= e(repo_url($repository, 'new-file?' . http_build_query($refQuery))) ?>">＋ <?= t('Create new file') ?></a>
                        </div>
                    </details>
                <?php endif; ?>
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
        <?php if (($search ?? '') !== ''): ?>
        <section class="file-browser search-results-panel">
            <header class="file-browser-header"><strong><?= t('Search results') ?> · “<?= e($search) ?>”</strong><span><?= count($searchResults) ?> <?= t('files') ?></span></header>
            <?php foreach ($searchResults as $found): ?>
                <a class="file-row" href="<?= e(repo_url($repository, 'blob?' . http_build_query(['ref' => $branch, 'path' => $found]))) ?>"><span class="file-type">FILE</span><span class="file-name"><?= e($found) ?></span><span class="file-commit"></span><span class="file-date"></span></a>
            <?php endforeach; ?>
            <?php if (!$searchResults): ?><div class="empty-state"><p><?= t('No matching files.') ?></p></div><?php endif; ?>
        </section>
        <?php endif; ?>
        <section class="file-browser">
            <header class="file-browser-header"><strong><?= $latest ? e($latest['subject']) : t('No commits yet') ?></strong><span><?= $latest ? e(short_sha($latest['sha'])) . ' · ' . e(format_date($latest['author_date'])) : '' ?></span></header>
            <?php if ($path !== ''): ?><?php $parent = dirname($path); ?><a class="file-row" href="<?= e(repo_url($repository, '?' . http_build_query(['ref' => $branch, 'path' => $parent === '.' ? '' : $parent]))) ?>"><span class="file-type">DIR</span><span class="file-name">..</span><span class="file-commit"></span><span class="file-date"></span></a><?php endif; ?>
            <?php foreach ($entries as $entry): ?>
                <?php $isDirectory = $entry['type'] === 'tree'; $target = $isDirectory ? repo_url($repository, '?' . http_build_query(['ref' => $branch, 'path' => $entry['path']])) : repo_url($repository, 'blob?' . http_build_query(['ref' => $branch, 'path' => $entry['path']])); ?>
                <a class="file-row" href="<?= e($target) ?>"><span class="file-type"><?= $isDirectory ? 'DIR' : 'FILE' ?></span><span class="file-name"><?= e($entry['name']) ?></span><span class="file-commit"><?= $isDirectory ? t('Directory') : ($entry['size'] !== null ? number_format($entry['size']) . ' B' : '') ?></span><span class="file-date"><?= e(short_sha($entry['sha'])) ?></span></a>
            <?php endforeach; ?>
            <?php if (!$entries && $path === ''): ?><div class="empty-state"><p><?= t('This repository has no files.') ?></p></div><?php endif; ?>
        </section>
        <?php if ($readme !== null): ?>
        <?php $toc = markdown_toc($readme); ?>
        <section class="readme-panel readme-shell" data-readme-shell>
            <div class="readme-title">
                <span>README.md / PROJECT DOCUMENT</span>
                <button type="button" class="toc-toggle" data-toc-toggle aria-expanded="true">☰ <?= t('Contents') ?></button>
            </div>
            <div class="readme-inner">
                <aside class="readme-toc" data-readme-toc>
                    <div class="toc-head"><span><?= t('On this page') ?></span></div>
                    <?php if ($toc): ?>
                        <nav class="toc-nav">
                            <?php foreach ($toc as $item): ?>
                                <a class="toc-link toc-level-<?= (int) $item['level'] ?>" href="#<?= e($item['id']) ?>"><?= e($item['text']) ?></a>
                            <?php endforeach; ?>
                        </nav>
                    <?php else: ?>
                        <p class="toc-empty"><?= t('No headings found.') ?></p>
                    <?php endif; ?>
                </aside>
                <div class="readme-content markdown-body"><?= render_markdown($readme) ?></div>
            </div>
        </section>
        <?php endif; ?>
    </div>
    <?php if (!empty($sidebar)): ?>
    <aside class="repo-sidebar">
        <?php require __DIR__ . '/../components/repository_sidebar.php'; ?>
    </aside>
    <?php endif; ?>
</div>
