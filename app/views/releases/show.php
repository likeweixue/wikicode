<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repository-body">
    <div class="repo-toolbar">
        <div class="breadcrumbs">
            <a href="<?= e(repo_url($repository, 'releases')) ?>"><?= t('Releases') ?></a>
            <span>/</span>
            <span><?= e($release['tag']) ?></span>
        </div>
        <?php if (\App\Core\Auth::canWrite($repository)): ?><a class="btn btn-small" href="<?= e(repo_url($repository, 'releases/new')) ?>">＋ <?= t('New release') ?></a><?php endif; ?>
    </div>

    <article class="tech-panel release-card">
        <div class="release-card-head">
            <span class="release-tag-badge"><?= e($release['tag']) ?></span>
            <strong class="release-title"><?= e($release['title']) ?></strong>
            <?php if ((int) $release['is_prerelease'] === 1): ?><span class="badge"><?= t('Pre-release') ?></span><?php endif; ?>
            <span class="release-meta"><?= e((string) ($release['author_username'] ?? '')) ?> · <?= e(format_date($release['created_at'])) ?></span>
        </div>

        <?php if (trim((string) $release['body']) !== ''): ?>
            <div class="release-notes-body markdown-body"><?= render_markdown((string) $release['body']) ?></div>
        <?php else: ?>
            <p class="release-meta"><?= t('This release has no notes.') ?></p>
        <?php endif; ?>

        <div class="release-assets">
            <div class="release-assets-head"><span class="section-code">ASSETS / <?= count($assets) ?></span></div>
            <?php foreach ($assets as $asset): ?>
                <div class="release-asset">
                    <a class="asset-name" href="<?= e(repo_url($repository, 'releases/' . $release['id'] . '/assets/' . $asset['id'])) ?>">⇩ <?= e($asset['filename']) ?></a>
                    <span class="asset-meta"><?= number_format((int) $asset['size']) ?> B · <?= (int) $asset['downloads'] ?> <?= t('downloads') ?></span>
                </div>
            <?php endforeach; ?>
            <?php if (!$assets): ?><p class="release-meta"><?= t('No attachments.') ?></p><?php endif; ?>

            <?php if (\App\Core\Auth::canWrite($repository)): ?>
                <form method="post" action="<?= e(repo_url($repository, 'releases/' . $release['id'] . '/assets')) ?>" class="upload-inline" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <label class="file-picker"><input type="file" name="assets[]" multiple required><span><?= t('Choose files') ?></span></label>
                    <button class="btn btn-small" type="submit"><?= t('Upload attachments') ?></button>
                </form>
            <?php endif; ?>
        </div>
    </article>
</div>
