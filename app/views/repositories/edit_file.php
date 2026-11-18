<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repository-body">
    <div class="repo-toolbar">
        <div class="breadcrumbs">
            <a href="<?= e(repo_url($repository, '?' . http_build_query(['ref' => $branch]))) ?>"><?= e($repository['name']) ?></a>
            <span>/</span>
            <a href="<?= e(repo_url($repository, 'blob?' . http_build_query(['ref' => $branch, 'path' => $path]))) ?>"><?= e(basename($path)) ?></a>
            <span>/</span>
            <span><?= t('Edit') ?></span>
        </div>
        <span class="badge"><?= e($branch) ?></span>
    </div>

    <?php if (!empty($error)): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>

    <section class="tech-panel form-panel edit-file-panel">
        <div class="panel-topline"><span><?= e($path) ?></span><b><?= t('Edit file') ?></b></div>
        <form method="post" action="<?= e(repo_url($repository, 'edit?' . http_build_query(['ref' => $branch, 'path' => $path]))) ?>" class="stack-form">
            <?= csrf_field() ?>
            <label><span><?= t('File content') ?></span>
                <textarea name="content" rows="22" class="code-editor" spellcheck="false"><?= e($_POST['content'] ?? $file['content']) ?></textarea>
            </label>
            <label><span><?= t('Commit message') ?></span>
                <input type="text" name="message" maxlength="200" value="<?= e($_POST['message'] ?? ('Update ' . $path)) ?>">
            </label>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><?= t('Commit changes') ?> →</button>
                <a class="btn btn-ghost" href="<?= e(repo_url($repository, 'blob?' . http_build_query(['ref' => $branch, 'path' => $path]))) ?>"><?= t('Cancel') ?></a>
            </div>
        </form>
    </section>
</div>
