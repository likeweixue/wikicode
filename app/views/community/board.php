<section class="page-heading forum-heading">
    <div>
        <span class="section-code"><?= e($category['name'] ?? t('Community')) ?> / BOARD</span>
        <h1><?= e($board['name']) ?></h1>
        <p><?= e($board['description'] ?: t('No description provided.')) ?></p>
    </div>
    <?php if ($currentUser): ?>
        <a class="btn btn-primary" href="/community/board/<?= (int) $board['id'] ?>/new">+ <?= t('New topic') ?></a>
    <?php else: ?>
        <a class="btn btn-ghost" href="/login"><?= t('Sign in to post') ?></a>
    <?php endif; ?>
</section>

<div class="forum-crumbs">
    <a href="/community"><?= t('Community') ?></a><span>/</span><span><?= e($board['name']) ?></span>
</div>

<section class="forum-table">
    <header class="forum-table-head">
        <span><?= t('Topic') ?></span>
        <span><?= t('Replies') ?></span>
        <span><?= t('Views') ?></span>
        <span><?= t('Last post') ?></span>
    </header>
    <?php if (!$topics): ?>
        <div class="forum-empty"><?= t('No topics yet. Be the first to post!') ?></div>
    <?php endif; ?>
    <?php foreach ($topics as $topic): ?>
        <a class="forum-topic-row" href="/community/topic/<?= (int) $topic['id'] ?>">
            <span class="topic-main">
                <strong><?= e($topic['title']) ?></strong>
                <small><?= e($topic['username']) ?> · <?= e(format_date($topic['created_at'])) ?></small>
            </span>
            <span class="topic-stat"><?= (int) $topic['reply_count'] ?></span>
            <span class="topic-stat"><?= (int) $topic['views'] ?></span>
            <span class="topic-last"><?= e(format_date($topic['updated_at'])) ?></span>
        </a>
    <?php endforeach; ?>
</section>
