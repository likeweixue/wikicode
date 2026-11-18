<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repository-body">
    <section class="form-panel tech-panel wide">
        <div class="panel-topline"><span><?= t('Repository settings') ?></span><b>CONFIGURATION / <?= e(str_pad((string) $repository['id'], 4, '0', STR_PAD_LEFT)) ?></b></div>
        <?php if (!empty($error)): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="<?= e(repo_url($repository, 'settings')) ?>" class="stack-form">
            <?= csrf_field() ?>
            <div class="settings-grid">
                <label><span><?= t('Repository name') ?></span><input type="text" name="name" required maxlength="50" pattern="[A-Za-z0-9][A-Za-z0-9._-]{0,49}" value="<?= e($repository['name']) ?>"></label>
                <label><span><?= t('Default branch') ?></span><select name="default_branch"><?php foreach ($branches as $branch): ?><option value="<?= e($branch) ?>" <?= $branch === $repository['default_branch'] ? 'selected' : '' ?>><?= e($branch) ?></option><?php endforeach; ?></select></label>
                <label class="full"><span><?= t('Description') ?></span><input type="text" name="description" maxlength="255" value="<?= e($repository['description']) ?>"></label>
                <label class="full"><span><?= t('Project homepage') ?></span><input type="url" name="homepage" maxlength="255" value="<?= e($repository['homepage'] ?? '') ?>" placeholder="https://example.com"></label>
                <label><span><?= t('Visibility') ?></span><select name="visibility"><option value="public" <?= $repository['visibility'] === 'public' ? 'selected' : '' ?>><?= t('Public') ?></option><option value="private" <?= $repository['visibility'] === 'private' ? 'selected' : '' ?>><?= t('Private') ?></option></select></label>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit"><?= t('Save changes') ?> →</button></div>
        </form>
    </section>
    <section class="danger-zone">
        <div class="panel-topline"><span><?= t('Danger zone') ?></span><b>IRREVERSIBLE</b></div>
        <div class="danger-row"><div><strong><?= t('Delete this repository') ?></strong><p><?= t('This permanently deletes the Git history, issues and pull requests.') ?></p></div><form method="post" action="<?= e(repo_url($repository, 'delete')) ?>" data-confirm="<?= e(t('Permanently delete this repository and all of its data?')) ?>"><?= csrf_field() ?><button class="btn btn-danger" type="submit"><?= t('Delete repository') ?></button></form></div>
    </section>
</div>
