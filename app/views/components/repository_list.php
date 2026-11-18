<?php $showOwner = $showOwner ?? false; ?>
<?php if (!$repositories): ?>
    <div class="empty-state">
        <span class="empty-cross" aria-hidden="true">+</span>
        <h3><?= t('No repositories in this sector') ?></h3>
        <p><?= t('Initialize a repository to begin recording your work.') ?></p>
        <?php if ($currentUser): ?><a class="btn btn-primary" href="/new"><?= t('New repository') ?> →</a><?php endif; ?>
    </div>
<?php else: ?>
    <div class="repository-list">
        <?php foreach ($repositories as $index => $repository): ?>
            <article class="repository-row">
                <div class="row-index"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></div>
                <div class="repository-main">
                    <div class="repository-title-line">
                        <?php if ($showOwner): ?><a class="owner-link" href="/<?= rawurlencode($repository['username']) ?>"><?= e($repository['username']) ?></a><span class="slash">/</span><?php endif; ?>
                        <a class="repository-name" href="<?= e(repo_url($repository)) ?>"><?= e($repository['name']) ?></a>
                        <span class="badge <?= $repository['visibility'] === 'private' ? 'badge-private' : '' ?>"><?= t(ucfirst($repository['visibility'])) ?></span>
                    </div>
                    <p><?= e($repository['description'] ?: t('No description provided.')) ?></p>
                    <div class="repository-meta">
                        <span><i class="language-dot"></i> GIT</span>
                        <span><?= e($repository['default_branch']) ?></span>
                        <?php if (isset($repository['open_issues'])): ?><span><?= e($repository['open_issues']) ?> <?= t('open issues') ?></span><?php endif; ?>
                        <span><?= t('Updated') ?> <?= e(format_date($repository['updated_at'])) ?></span>
                    </div>
                </div>
                <a class="row-arrow" href="<?= e(repo_url($repository)) ?>" aria-label="<?= t('Open repository') ?>">↗</a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
