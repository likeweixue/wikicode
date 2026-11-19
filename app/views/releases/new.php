<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repository-body">
    <div class="repo-toolbar">
        <div class="breadcrumbs">
            <a href="<?= e(repo_url($repository, 'releases')) ?>"><?= t('Releases') ?></a>
            <span>/</span>
            <span><?= t('New release') ?></span>
        </div>
        <span class="section-code">RELEASE / NEW</span>
    </div>

    <?php if (!empty($error)): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>

    <section class="tech-panel form-panel wide">
        <div class="panel-topline"><span><?= t('New release') ?></span><b>PUBLISH / TAGGED</b></div>
        <form method="post" action="<?= e(repo_url($repository, 'releases')) ?>" class="stack-form" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <label><span><?= t('Tag name') ?> <i class="required">*</i></span>
                <input type="text" name="tag" required maxlength="100" list="tag-options" value="<?= e($values['tag']) ?>" placeholder="v1.0.0" autofocus>
                <datalist id="tag-options"><?php foreach ($tags as $tag): ?><option value="<?= e($tag) ?>"></option><?php endforeach; ?></datalist>
                <small><?= t('An existing tag can be used, or a new tag will be created on the target branch.') ?></small>
            </label>
            <label><span><?= t('Target branch') ?></span>
                <select name="branch">
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= e($branch) ?>" <?= $branch === $values['branch'] ? 'selected' : '' ?>><?= e($branch) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span><?= t('Release title') ?></span>
                <input type="text" name="title" maxlength="200" value="<?= e($values['title']) ?>" placeholder="<?= e($values['tag'] !== '' ? $values['tag'] : 'v1.0.0') ?>">
            </label>
            <label><span><?= t('Release notes') ?></span>
                <textarea name="body" rows="12" placeholder="<?= t('Describe what changed in this release…') ?>"><?= e($values['body']) ?></textarea>
                <small><?= t('Basic Markdown headings, lists, bold and inline code are supported.') ?></small>
            </label>
            <label><span><?= t('Attach files') ?></span>
                <label class="file-picker"><input type="file" name="assets[]" multiple><span><?= t('Choose files') ?></span></label>
                <small><?= t('Optional. Binaries, archives or any release artifacts (up to 64 MB per file).') ?></small>
            </label>
            <div class="form-options">
                <label class="checkbox-line"><input type="checkbox" name="prerelease" value="1" <?= $values['prerelease'] ? 'checked' : '' ?>><span><?= t('Mark as a pre-release') ?></span></label>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><?= t('Publish release') ?> →</button>
                <a class="btn btn-ghost" href="<?= e(repo_url($repository, 'releases')) ?>"><?= t('Cancel') ?></a>
            </div>
        </form>
    </section>
</div>
