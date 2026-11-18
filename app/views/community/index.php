<section class="page-heading forum-heading">
    <div>
        <span class="section-code">COMMUNITY / FORUM</span>
        <h1><?= t('Community') ?></h1>
        <p><?= t('Discuss ideas, ask questions and share what you are building.') ?></p>
    </div>
    <div class="forum-totals">
        <span><strong><?= (int) $totals['topics'] ?></strong><?= t('Topics') ?></span>
        <span><strong><?= (int) $totals['posts'] ?></strong><?= t('Posts') ?></span>
        <span><strong><?= (int) $totals['members'] ?></strong><?= t('Members') ?></span>
    </div>
</section>

<?php foreach ($categories as $index => $category): ?>
<section class="forum-category">
    <header class="forum-category-head">
        <div>
            <span class="section-code">SECTION / <?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
            <h2><?= e($category['name']) ?></h2>
        </div>
        <p><?= e($category['description']) ?></p>
    </header>
    <div class="forum-board-list">
        <?php if (!$category['boards']): ?><p class="forum-empty"><?= t('No boards yet.') ?></p><?php endif; ?>
        <?php foreach ($category['boards'] as $board): ?>
            <a class="forum-board" href="/community/board/<?= (int) $board['id'] ?>">
                <span class="board-icon" aria-hidden="true">▣</span>
                <span class="board-main">
                    <strong><?= e($board['name']) ?></strong>
                    <small><?= e($board['description'] ?: t('No description provided.')) ?></small>
                </span>
                <span class="board-stats"><b><?= (int) $board['topic_count'] ?></b><small><?= t('Topics') ?></small></span>
                <span class="board-stats"><b><?= (int) $board['post_count'] ?></b><small><?= t('Replies') ?></small></span>
                <span class="board-last">
                    <?php if ($board['last_topic']): ?>
                        <strong><?= e($board['last_topic']) ?></strong>
                        <small><?= e($board['last_author']) ?> · <?= e(format_date($board['last_at'])) ?></small>
                    <?php else: ?>
                        <small><?= t('No topics yet') ?></small>
                    <?php endif; ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endforeach; ?>
