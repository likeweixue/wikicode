<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repository-body">
    <div class="list-toolbar">
        <div class="filter-tabs">
            <a class="<?= active_class($status === 'open') ?>" href="<?= e(repo_url($repository, 'issues?status=open')) ?>"><?= e($counts['open']) ?> <?= t('Open') ?></a>
            <a class="<?= active_class($status === 'closed') ?>" href="<?= e(repo_url($repository, 'issues?status=closed')) ?>"><?= e($counts['closed']) ?> <?= t('Closed') ?></a>
        </div>
        <?php if ($currentUser): ?><a class="btn btn-primary" href="<?= e(repo_url($repository, 'issues/new')) ?>">+ <?= t('New issue') ?></a><?php else: ?><a class="btn" href="/login"><?= t('Sign in to participate') ?></a><?php endif; ?>
    </div>
    <?php if (!$issues): ?>
        <div class="empty-state tech-panel"><span class="empty-cross">+</span><h3><?= $status === 'open' ? t('No open issues') : t('No closed issues') ?></h3><p><?= t('This discussion channel is clear.') ?></p></div>
    <?php else: ?>
        <div class="issue-list">
            <?php foreach ($issues as $issue): ?>
                <article class="issue-row">
                    <span class="issue-state-icon <?= $issue['status'] === 'closed' ? 'closed' : '' ?>"><?= $issue['status'] === 'closed' ? '✓' : '!' ?></span>
                    <div><a class="issue-title" href="<?= e(repo_url($repository, 'issues/' . $issue['number'])) ?>"><?= e($issue['title']) ?></a><div class="issue-meta">#<?= e($issue['number']) ?> · <?= e($issue['username']) ?> · <?= e(format_date($issue['created_at'])) ?><?php if ($issue['comment_count']): ?> · <?= e($issue['comment_count']) ?> <?= t('comments') ?><?php endif; ?></div></div>
                    <span class="issue-number">ISS—<?= e(str_pad((string) $issue['number'], 4, '0', STR_PAD_LEFT)) ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
