<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repository-body">
    <header class="detail-header">
        <span class="section-code">PULL REQUEST / PR—<?= e(str_pad((string) $pullRequest['number'], 4, '0', STR_PAD_LEFT)) ?></span>
        <h1><?= e($pullRequest['title']) ?> <span>#<?= e($pullRequest['number']) ?></span></h1>
        <div class="detail-meta"><span class="badge badge-<?= e($pullRequest['status']) ?>"><?= t(ucfirst($pullRequest['status'])) ?></span><span><b><?= e($pullRequest['username']) ?></b> <?= t('proposes merging') ?></span><span><?= count($commits) ?> <?= t('commits') ?></span></div>
        <div class="branch-route"><span><?= e($pullRequest['head_branch']) ?></span><b>MERGE INTO →</b><span><?= e($pullRequest['base_branch']) ?></span></div>
    </header>

    <?php if ($pullRequest['body'] !== ''): ?><article class="comment-box" style="margin-top:22px"><header class="comment-header"><strong><?= e($pullRequest['username']) ?></strong><time><?= e(format_date($pullRequest['created_at'])) ?></time></header><div class="comment-body markdown-body"><?= render_markdown($pullRequest['body']) ?></div></article><?php endif; ?>

    <?php if ($pullRequest['status'] === 'merged'): ?>
        <section class="merge-box"><span class="badge badge-merged"><?= t('Merged') ?></span><h3><?= t('Changes have entered the target branch') ?></h3><p><?= t('Merge commit') ?>: <a class="commit-sha" href="<?= e(repo_url($repository, 'commit/' . $pullRequest['merge_commit'])) ?>"><?= e($pullRequest['merge_commit']) ?></a> · <?= e(format_date($pullRequest['merged_at'])) ?></p></section>
    <?php elseif ($pullRequest['status'] === 'open'): ?>
        <section class="merge-box">
            <span class="section-code">MERGE CONTROL / ATOMIC UPDATE</span>
            <h3><?= $hasChanges ? t('Ready for merge evaluation') : t('No new changes to merge') ?></h3>
            <p><?= t('Merging creates a non-fast-forward merge commit. Branch changes and conflicts are checked before any update.') ?></p>
            <?php if ($currentUser && \App\Core\Auth::canWrite($repository)): ?>
                <div class="form-actions">
                    <form method="post" action="<?= e(repo_url($repository, 'pulls/' . $pullRequest['number'] . '/merge')) ?>" data-confirm="<?= e(t('Merge this pull request into the base branch?')) ?>"><?= csrf_field() ?><input type="hidden" name="base_sha" value="<?= e($pullRequest['base_sha']) ?>"><input type="hidden" name="head_sha" value="<?= e($pullRequest['head_sha']) ?>"><button class="btn btn-primary" type="submit" <?= !$hasChanges || $compareError ? 'disabled' : '' ?>><?= t('Merge pull request') ?> →</button></form>
                    <form method="post" action="<?= e(repo_url($repository, 'pulls/' . $pullRequest['number'] . '/toggle')) ?>"><?= csrf_field() ?><button class="btn" type="submit"><?= t('Close pull request') ?></button></form>
                </div>
            <?php elseif ($currentUser && (int) $currentUser['id'] === (int) $pullRequest['author_id']): ?>
                <form method="post" action="<?= e(repo_url($repository, 'pulls/' . $pullRequest['number'] . '/toggle')) ?>"><?= csrf_field() ?><button class="btn" type="submit"><?= t('Close pull request') ?></button></form>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <section class="merge-box"><span class="badge badge-closed"><?= t('Closed') ?></span><h3><?= t('This proposal is closed') ?></h3><?php if ($currentUser && (\App\Core\Auth::canWrite($repository) || (int) $currentUser['id'] === (int) $pullRequest['author_id'])): ?><form method="post" action="<?= e(repo_url($repository, 'pulls/' . $pullRequest['number'] . '/toggle')) ?>"><?= csrf_field() ?><button class="btn" type="submit"><?= t('Reopen pull request') ?></button></form><?php endif; ?></section>
    <?php endif; ?>

    <section class="section-block compact-section">
        <div class="section-heading"><div><span class="section-code">CHANGESET / COMMITS</span><h2><?= t('Commits in this request') ?></h2></div><span class="section-count"><?= count($commits) ?></span></div>
        <div class="commit-list">
            <?php foreach ($commits as $commit): ?><article class="commit-row"><div><strong><?= e($commit['subject']) ?></strong><small><?= e($commit['author']) ?></small></div><small><?= e(format_date($commit['date'])) ?></small><a class="commit-sha" href="<?= e(repo_url($repository, 'commit/' . $commit['sha'])) ?>"><?= e(short_sha($commit['sha'])) ?></a></article><?php endforeach; ?>
            <?php if (!$commits): ?><div class="empty-state"><p><?= t('No unique commits in this comparison.') ?></p></div><?php endif; ?>
        </div>
    </section>

    <section class="diff-panel">
        <div class="diff-header"><span>DIFF / <?= e(short_sha($pullRequest['base_sha'])) ?>...<?= e(short_sha($pullRequest['head_sha'])) ?></span><?php if ($diff['truncated']): ?><b><?= t('Large diff truncated at 3 MB') ?></b><?php endif; ?></div>
        <div class="diff-content"><?php if ($compareError): ?><span class="diff-line diff-del"><?= e($compareError) ?></span><?php elseif ($diff['content'] === ''): ?><span class="diff-line"><?= t('No textual differences.') ?></span><?php else: ?><?php foreach (explode("\n", $diff['content']) as $line): ?><?php $class = str_starts_with($line, '+') && !str_starts_with($line, '+++') ? 'diff-add' : (str_starts_with($line, '-') && !str_starts_with($line, '---') ? 'diff-del' : (str_starts_with($line, '@@') || str_starts_with($line, 'diff ') ? 'diff-meta' : '')); ?><span class="diff-line <?= $class ?>"><?= e($line) ?: ' ' ?></span><?php endforeach; ?><?php endif; ?></div>
    </section>
</div>
