<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repo-main repo-main-full" style="max-width: 900px; margin: 0 auto;">
    <section class="tech-panel form-panel">
        <div class="panel-topline"><span><?= t($action === 'edit' ? 'Edit wiki page' : 'New wiki page') ?></span><b>WIKI / MARKDOWN</b></div>
        <?php if (!empty($error)): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="stack-form">
            <?= csrf_field() ?>
            <label><span><?= t('Title') ?> <i class="required">*</i></span>
                <input type="text" name="title" required maxlength="200" value="<?= e($page['title'] ?? '') ?>" placeholder="<?= t('Page title') ?>">
            </label>
            <?php if (!empty($parentSlugs)): ?>
            <label><span><?= t('Parent page') ?></span>
                <select name="parent_slug">
                    <option value=""><?= t('(Top level)') ?></option>
                    <?php foreach ($parentSlugs as $p): ?>
                        <option value="<?= e($p['slug']) ?>" <?= ($page['parent_slug'] ?? '') === $p['slug'] ? 'selected' : '' ?>><?= e($p['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php endif; ?>
            <label><span><?= t('Content') ?> (<?= t('Markdown supported') ?>)</span>
                <textarea name="body" rows="18" placeholder="# Heading&#10;&#10;Some content..."><?= e($page['body'] ?? '') ?></textarea>
            </label>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><?= t($action === 'edit' ? 'Save changes' : 'Create page') ?> →</button>
                <a class="btn btn-ghost" href="<?= e(repo_url($repository, 'wiki')) ?>"><?= t('Cancel') ?></a>
            </div>
        </form>
    </section>
</div>
