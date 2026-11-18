<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repo-main repo-main-full" style="max-width: 860px; margin: 0 auto;">
    <section class="tech-panel form-panel">
        <div class="panel-topline"><span><?= t('Add file') ?></span><b><?= e($branch) ?><?= $path !== '' ? ' / ' . e($path) : '' ?></b></div>
        <?php if (!empty($error)): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data" class="stack-form" action="<?= e(repo_url($repository, 'new-file')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="ref" value="<?= e($branch) ?>">
            <input type="hidden" name="path" value="<?= e($path) ?>">
            <label><span><?= t('File name') ?> <i class="required">*</i></span>
                <input type="text" name="file_path" maxlength="255" placeholder="src/example.php" value="<?= e($_POST['file_path'] ?? '') ?>">
                <small class="field-hint"><?= $path !== '' ? e($path . '/') : '' ?><?= t('Use / to create subdirectories.') ?></small>
            </label>
            <label><span><?= t('Or upload a file') ?></span>
                <input type="file" name="upload">
                <small class="field-hint"><?= t('Max 2 MiB. If a name is left empty, the uploaded file name is used.') ?></small>
            </label>
            <label><span><?= t('File content') ?></span>
                <textarea name="content" rows="14" placeholder="<?= t('File content...') ?>"><?= e($_POST['content'] ?? '') ?></textarea>
            </label>
            <label><span><?= t('Commit message') ?></span>
                <input type="text" name="message" maxlength="255" placeholder="<?= t('Create new file') ?>">
            </label>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><?= t('Commit new file') ?> →</button>
                <a class="btn btn-ghost" href="<?= e(repo_url($repository, '?' . http_build_query(['ref' => $branch, 'path' => $path]))) ?>"><?= t('Cancel') ?></a>
            </div>
        </form>
    </section>
</div>
