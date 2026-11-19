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
                        <div class="dropdown-foot">
                            <a class="dropdown-item" href="<?= e(repo_url($repository, 'tags')) ?>"><?= t('View all tags') ?> ↗</a>
                            <a class="dropdown-item" href="<?= e(repo_url($repository, 'releases')) ?>"><?= t('Releases') ?> ↗</a>
                            <?php if (\App\Core\Auth::canWrite($repository)): ?><a class="dropdown-item" href="<?= e(repo_url($repository, 'releases/new')) ?>">＋ <?= t('New release') ?></a><?php endif; ?>
                        </div>
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
            <header class="file-browser-header"><strong><?= $latest ? e($latest['subject']) : t('No commits yet') ?></strong><span class="file-browser-meta"><a href="<?= e(repo_url($repository, 'commits?' . http_build_query(['ref' => $branch]))) ?>"><?= (int) ($commitCount ?? 0) ?> <?= t('commits') ?></a><?= $latest ? ' · ' . e(short_sha($latest['sha'])) . ' · ' . e(format_date($latest['author_date'])) : '' ?></span></header>
            <?php if ($path !== ''): ?><?php $parent = dirname($path); ?><a class="file-row" href="<?= e(repo_url($repository, '?' . http_build_query(['ref' => $branch, 'path' => $parent === '.' ? '' : $parent]))) ?>"><span class="file-type">DIR</span><span class="file-name">..</span><span class="file-commit"></span><span class="file-date"></span></a><?php endif; ?>
            <?php foreach ($entries as $entry): ?>
                <?php $isDirectory = $entry['type'] === 'tree'; $target = $isDirectory ? repo_url($repository, '?' . http_build_query(['ref' => $branch, 'path' => $entry['path']])) : repo_url($repository, 'blob?' . http_build_query(['ref' => $branch, 'path' => $entry['path']])); ?>
                <?php $commitInfo = $entryCommits[$entry['path']] ?? null; ?>
                <a class="file-row" href="<?= e($target) ?>"><span class="file-type"><?= $isDirectory ? 'DIR' : 'FILE' ?></span><span class="file-name"><?= e($entry['name']) ?></span><span class="file-commit" title="<?= e((string) ($commitInfo['subject'] ?? '')) ?>"><?= $commitInfo ? e((string) $commitInfo['subject']) : '' ?></span><span class="file-date" title="<?= e((string) ($commitInfo['sha'] ?? '')) ?>"><?= $commitInfo ? e(format_date((string) $commitInfo['date'])) : '' ?></span></a>
            <?php endforeach; ?>
            <?php if (!$entries && $path === ''): ?><div class="empty-state"><p><?= t('This repository has no files.') ?></p></div><?php endif; ?>
        </section>
        <?php if ($readme !== null || !empty($projectDocs)): ?>
        <?php
        $projectDocs = $projectDocs ?? [];
        // 组装 tabs：README 总是第一个（如果存在）
        $tabs = [];
        if ($readme !== null) {
            $tabs[] = ['key' => 'readme', 'label' => 'README', 'path' => 'README.md', 'content' => $readme, 'html' => render_markdown($readme), 'toc' => markdown_toc($readme)];
        }
        foreach ($projectDocs as $d) {
            $tabs[] = ['key' => 'doc-' . md5($d['path']), 'label' => $d['label'], 'path' => $d['path'], 'content' => $d['content'], 'html' => $d['html'], 'toc' => $d['toc']];
        }
        $activeKey = $_GET['doc'] ?? ($tabs[0]['key'] ?? 'readme');
        // 找当前激活 tab 的 path，用于"编辑"按钮
        $activeTabPath = 'README.md';
        foreach ($tabs as $tab) {
            if ($tab['key'] === $activeKey) { $activeTabPath = $tab['path']; break; }
        }
        $editBase = repo_url($repository, 'edit?' . http_build_query(['ref' => $branch]));
        $canWriteDoc = \App\Core\Auth::canWrite($repository);
        ?>
        <section class="readme-panel readme-shell" data-readme-shell data-doc-shell>
            <div class="readme-title">
                <span data-doc-title><?= e($tabs[0]['path'] ?? 'README.md') ?> / PROJECT DOCUMENT</span>
                <div class="readme-title-actions">
                    <?php if ($canWriteDoc): ?>
                        <a class="readme-doc-edit" href="<?= e($editBase . '&path=' . urlencode($activeTabPath)) ?>" data-doc-edit="<?= e($activeTabPath) ?>" data-edit-base="<?= e($editBase . '&path=') ?>" title="<?= t('Edit') . ': ' . $activeTabPath ?>">✎ <?= t('Edit') ?></a>
                    <?php endif; ?>
                    <?php foreach ($tabs as $tab): ?>
                        <?php $isActive = $tab['key'] === $activeKey; ?>
                        <button type="button" class="readme-doc-link<?= $isActive ? ' is-active' : '' ?>" data-doc-tab="<?= e($tab['key']) ?>" data-doc-path="<?= e($tab['path']) ?>" aria-pressed="<?= $isActive ? 'true' : 'false' ?>"><?= e($tab['label']) ?></button>
                    <?php endforeach; ?>
                    <button type="button" class="toc-toggle" data-toc-toggle aria-expanded="true">☰ <?= t('Contents') ?></button>
                </div>
            </div>
            <div class="readme-inner">
                <?php foreach ($tabs as $tab): ?>
                    <?php $isActive = $tab['key'] === $activeKey; ?>
                    <aside class="readme-toc<?= $isActive ? ' is-active' : '' ?>" data-doc-toc="<?= e($tab['key']) ?>"<?= $isActive ? '' : ' hidden' ?>>
                        <div class="toc-head"><span><?= t('On this page') ?></span></div>
                        <?php if (!empty($tab['toc'])): ?>
                            <nav class="toc-nav">
                                <?php foreach ($tab['toc'] as $item): ?>
                                    <a class="toc-link toc-level-<?= (int) $item['level'] ?>" href="#<?= e($item['id']) ?>"><?= e($item['text']) ?></a>
                                <?php endforeach; ?>
                            </nav>
                        <?php else: ?>
                            <p class="toc-empty"><?= t('No headings found.') ?></p>
                        <?php endif; ?>
                    </aside>
                <?php endforeach; ?>
                <?php foreach ($tabs as $tab): ?>
                    <?php $isActive = $tab['key'] === $activeKey; ?>
                    <div class="readme-doc-body<?= $isActive ? ' is-active' : '' ?>" data-doc-body="<?= e($tab['key']) ?>"<?= $isActive ? '' : ' hidden' ?>>
                        <?php if ($tab['html'] !== null): ?>
                            <div class="readme-content markdown-body"><?= $tab['html'] ?></div>
                        <?php else: ?>
                            <div class="readme-content markdown-body"><p class="muted"><?= t('Unable to render this file.') ?></p></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
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
