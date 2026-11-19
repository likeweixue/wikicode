<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repository-body">
    <div class="repo-toolbar">
        <span class="section-code">RELEASES / <?= count($releases) ?></span>
        <?php if (\App\Core\Auth::canWrite($repository)): ?>
            <a class="btn btn-small btn-primary" href="<?= e(repo_url($repository, 'releases/new')) ?>"><?= t('New release') ?> →</a>
        <?php endif; ?>
    </div>

    <div class="release-stack">
        <?php foreach ($releases as $release): ?>
            <article class="tech-panel release-card">
                <div class="release-card-head">
                    <a class="release-tag-badge" href="<?= e(repo_url($repository, 'releases/' . $release['id'])) ?>"><?= e($release['tag']) ?></a>
                    <a class="release-title" href="<?= e(repo_url($repository, 'releases/' . $release['id'])) ?>"><?= e($release['title']) ?></a>
                    <?php if ((int) $release['is_prerelease'] === 1): ?><span class="badge"><?= t('Pre-release') ?></span><?php endif; ?>
                    <span class="release-meta"><?= e((string) ($release['author_username'] ?? '')) ?> · <?= e(format_date($release['created_at'])) ?></span>
                </div>
                <?php if (trim((string) $release['body']) !== ''): ?>
                    <div class="release-notes-body markdown-body"><?= render_markdown((string) $release['body']) ?></div>
                <?php endif; ?>
                <?php if ((int) $release['asset_count'] > 0): ?>
                    <div class="release-assets">
                        <span class="release-meta"><?= (int) $release['asset_count'] ?> <?= t('attachments') ?> · <a href="<?= e(repo_url($repository, 'releases/' . $release['id'])) ?>"><?= t('Download') ?> ↗</a></span>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>

        <?php if (!$releases): ?>
            <div class="tech-panel release-empty">
                <p><?= t('No releases published') ?></p>
                <?php if (\App\Core\Auth::canWrite($repository)): ?>
                    <a class="btn btn-small" href="<?= e(repo_url($repository, 'releases/new')) ?>">＋ <?= t('New release') ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($unreleasedTags): ?>
        <section class="tech-panel release-card" style="margin-top: 18px">
            <div class="release-card-head"><strong class="release-title"><?= t('Tags without a release') ?></strong></div>
            <ul class="tag-list">
                <?php foreach ($unreleasedTags as $tag): ?>
                    <li>
                        <a class="tag-name" href="<?= e(repo_url($repository, '?' . http_build_query(['ref' => $tag]))) ?>">🏷 <?= e($tag) ?></a>
                        <span class="tag-state"><?= t('No release') ?></span>
                        <?php if (\App\Core\Auth::canWrite($repository)): ?><a class="tag-action" href="<?= e(repo_url($repository, 'releases/new?' . http_build_query(['tag' => $tag]))) ?>"><?= t('Publish release') ?> ↗</a><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
</div>
