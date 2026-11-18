<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repository-body">
    <div class="list-toolbar">
        <div class="filter-tabs">
            <a class="<?= active_class($status === 'open') ?>" href="<?= e(repo_url($repository, 'pulls?status=open')) ?>"><?= e($counts['open']) ?> <?= t('Open') ?></a>
            <a class="<?= active_class($status === 'merged') ?>" href="<?= e(repo_url($repository, 'pulls?status=merged')) ?>"><?= e($counts['merged']) ?> <?= t('Merged') ?></a>
            <a class="<?= active_class($status === 'closed') ?>" href="<?= e(repo_url($repository, 'pulls?status=closed')) ?>"><?= e($counts['closed']) ?> <?= t('Closed') ?></a>
        </div>
        <?php if ($currentUser): ?><a class="btn btn-primary" href="<?= e(repo_url($repository, 'pulls/new')) ?>">+ <?= t('New pull request') ?></a><?php endif; ?>
    </div>
    <?php if (!$pullRequests): ?>
        <div class="empty-state tech-panel"><span class="empty-cross">+</span><h3><?= t('No pull requests in this state') ?></h3><p><?= t('Compare two branches to propose a new change.') ?></p></div>
    <?php else: ?>
        <div class="issue-list">
            <?php foreach ($pullRequests as $pull): ?>
                <article class="issue-row">
                    <span class="issue-state-icon <?= e($pull['status']) ?>"><?= $pull['status'] === 'merged' ? '↗' : ($pull['status'] === 'closed' ? '×' : '+') ?></span>
                    <div><a class="issue-title" href="<?= e(repo_url($repository, 'pulls/' . $pull['number'])) ?>"><?= e($pull['title']) ?></a><div class="issue-meta">#<?= e($pull['number']) ?> · <?= e($pull['username']) ?> · <span class="mono"><?= e($pull['head_branch']) ?> → <?= e($pull['base_branch']) ?></span> · <?= e(format_date($pull['created_at'])) ?></div></div>
                    <span class="issue-number">PR—<?= e(str_pad((string) $pull['number'], 4, '0', STR_PAD_LEFT)) ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
