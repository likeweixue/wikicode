<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repository-body">
    <header class="detail-header">
        <span class="section-code">ISSUE RECORD / ISS—<?= e(str_pad((string) $issue['number'], 4, '0', STR_PAD_LEFT)) ?></span>
        <h1><?= e($issue['title']) ?> <span>#<?= e($issue['number']) ?></span></h1>
        <div class="detail-meta"><span class="badge <?= $issue['status'] === 'open' ? 'badge-open' : 'badge-closed' ?>"><?= t(ucfirst($issue['status'])) ?></span><span><b><?= e($issue['username']) ?></b> <?= t('opened this issue') ?></span><time><?= e(format_date($issue['created_at'])) ?></time><span><?= count($comments) ?> <?= t('comments') ?></span></div>
    </header>
    <div class="timeline">
        <article class="comment">
            <span class="avatar"><?= e(strtoupper(substr($issue['username'], 0, 2))) ?></span>
            <div class="comment-box"><header class="comment-header"><strong><?= e($issue['username']) ?></strong><time><?= e(format_date($issue['created_at'])) ?></time></header><div class="comment-body markdown-body"><?= $issue['body'] !== '' ? render_markdown($issue['body']) : '<span class="muted">' . e(t('No description provided.')) . '</span>' ?></div></div>
        </article>
        <?php foreach ($comments as $comment): ?>
            <article class="comment">
                <span class="avatar"><?= e(strtoupper(substr($comment['username'], 0, 2))) ?></span>
                <div class="comment-box"><header class="comment-header"><strong><?= e($comment['username']) ?></strong><time><?= e(format_date($comment['created_at'])) ?></time></header><div class="comment-body markdown-body"><?= render_markdown($comment['body']) ?></div></div>
            </article>
        <?php endforeach; ?>
        <?php if ($currentUser): ?>
            <div class="comment-form stack-form">
                <form id="issue-comment-form" method="post" action="<?= e(repo_url($repository, 'issues/' . $issue['number'] . '/comments')) ?>">
                    <?= csrf_field() ?>
                    <label><span><?= t('Add to the discussion') ?></span><textarea name="body" required rows="5" placeholder="<?= t('Write a focused, constructive comment…') ?>"></textarea></label>
                </form>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit" form="issue-comment-form"><?= t('Comment') ?> →</button>
                    <?php if (\App\Core\Auth::canWrite($repository) || (int) $currentUser['id'] === (int) $issue['author_id']): ?>
                        <form class="compact-form" method="post" action="<?= e(repo_url($repository, 'issues/' . $issue['number'] . '/toggle')) ?>"><?= csrf_field() ?><button class="btn" type="submit"><?= $issue['status'] === 'open' ? t('Close issue') : t('Reopen issue') ?></button></form>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?><p><a class="btn" href="/login"><?= t('Sign in to participate') ?></a></p><?php endif; ?>
    </div>
</div>
