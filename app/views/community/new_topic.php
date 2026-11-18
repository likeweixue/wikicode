<div class="forum-crumbs">
    <a href="/community"><?= t('Community') ?></a><span>/</span>
    <a href="/community/board/<?= (int) $board['id'] ?>"><?= e($board['name']) ?></a><span>/</span>
    <span><?= t('New topic') ?></span>
</div>

<section class="tech-panel form-panel forum-form-panel">
    <div class="panel-topline"><span><?= t('New topic') ?></span><b><?= e($board['name']) ?></b></div>
    <?php if (!empty($error)): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="/community/board/<?= (int) $board['id'] ?>/new" class="stack-form">
        <?= csrf_field() ?>
        <label><span><?= t('Title') ?> <i class="required">*</i></span>
            <input type="text" name="title" maxlength="200" value="<?= e($values['title'] ?? '') ?>" placeholder="<?= t('What do you want to discuss?') ?>" required>
        </label>
        <label><span><?= t('Message') ?> <i class="required">*</i></span>
            <textarea name="body" rows="12" placeholder="<?= t('Write your message...') ?>" required><?= e($values['body'] ?? '') ?></textarea>
            <small class="field-hint"><?= t('Markdown supported') ?></small>
        </label>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= t('Publish topic') ?> →</button>
            <a class="btn btn-ghost" href="/community/board/<?= (int) $board['id'] ?>"><?= t('Cancel') ?></a>
        </div>
    </form>
</section>
