<div class="forum-crumbs">
    <a href="/community"><?= t('Community') ?></a><span>/</span>
    <?php if ($board): ?><a href="/community/board/<?= (int) $board['id'] ?>"><?= e($board['name']) ?></a><span>/</span><?php endif; ?>
    <span><?= e($topic['title']) ?></span>
</div>

<section class="page-heading forum-heading">
    <div>
        <span class="section-code">TOPIC / <?= str_pad((string) $topic['id'], 4, '0', STR_PAD_LEFT) ?></span>
        <h1><?= e($topic['title']) ?></h1>
        <p><?= e($topic['username']) ?> · <?= e(format_date($topic['created_at'])) ?> · <?= (int) $topic['views'] ?> <?= t('Views') ?></p>
    </div>
</section>

<div class="forum-post-list">
    <article class="forum-post">
        <aside class="post-author">
            <span class="post-avatar"><?= e(strtoupper(substr($topic['username'], 0, 2))) ?></span>
            <strong><?= e($topic['username']) ?></strong>
            <small><?= t('Author') ?></small>
        </aside>
        <div class="post-body">
            <div class="post-meta">#1 · <?= e(format_date($topic['created_at'])) ?></div>
            <div class="post-content"><?= render_markdown($topic['body']) ?></div>
        </div>
    </article>
    <?php foreach ($posts as $index => $post): ?>
        <article class="forum-post" id="reply-<?= (int) $post['id'] ?>">
            <aside class="post-author">
                <span class="post-avatar"><?= e(strtoupper(substr($post['username'], 0, 2))) ?></span>
                <strong><?= e($post['username']) ?></strong>
                <small><?= t('Member') ?></small>
            </aside>
            <div class="post-body">
                <div class="post-meta">#<?= $index + 2 ?> · <?= e(format_date($post['created_at'])) ?></div>
                <div class="post-content"><?= render_markdown($post['body']) ?></div>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php if ($currentUser): ?>
    <?php if ((int) $topic['is_locked'] === 1): ?>
        <div class="forum-empty"><?= t('This topic is locked.') ?></div>
    <?php else: ?>
        <section class="tech-panel form-panel forum-reply-panel">
            <div class="panel-topline"><span><?= t('Post reply') ?></span><b><?= t('Markdown supported') ?></b></div>
            <form method="post" action="/community/topic/<?= (int) $topic['id'] ?>/reply" class="stack-form">
                <?= csrf_field() ?>
                <label><span><?= t('Message') ?></span>
                    <textarea name="body" rows="8" placeholder="<?= t('Write your reply...') ?>" required></textarea>
                </label>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit"><?= t('Reply') ?> →</button>
                </div>
            </form>
        </section>
    <?php endif; ?>
<?php else: ?>
    <div class="forum-empty"><a href="/login"><?= t('Sign in') ?></a> <?= t('to join the discussion.') ?></div>
<?php endif; ?>
