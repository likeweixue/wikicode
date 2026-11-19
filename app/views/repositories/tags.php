<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repository-body">
    <div class="repo-toolbar">
        <span class="section-code">TAGS / <?= count($tags) ?></span>
        <a class="btn btn-small" href="<?= e(repo_url($repository, 'releases')) ?>"><?= t('Releases') ?> ↗</a>
    </div>

    <section class="tech-panel release-card">
        <div class="release-card-head"><strong class="release-title"><?= t('Tags') ?></strong></div>
        <?php if ($tags): ?>
            <ul class="tag-list">
                <?php foreach ($tags as $tag): ?>
                    <li>
                        <a class="tag-name" href="<?= e(repo_url($repository, '?' . http_build_query(['ref' => $tag['name']]))) ?>">🏷 <?= e($tag['name']) ?></a>
                        <?php if ($tag['release']): ?>
                            <span class="tag-state"><?= t('Release') ?></span>
                            <a class="tag-action" href="<?= e(repo_url($repository, 'releases/' . $tag['release']['id'])) ?>"><?= t('View') ?> ↗</a>
                        <?php else: ?>
                            <span class="tag-state"><?= t('No release') ?></span>
                            <?php if (\App\Core\Auth::canWrite($repository)): ?><a class="tag-action" href="<?= e(repo_url($repository, 'releases/new?' . http_build_query(['tag' => $tag['name']]))) ?>"><?= t('Publish release') ?> ↗</a><?php endif; ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="release-meta"><?= t('No tags yet.') ?></p>
        <?php endif; ?>
    </section>

    <?php if (\App\Core\Auth::canWrite($repository)): ?>
        <section class="tech-panel form-panel wide" style="margin-top: 18px">
            <div class="panel-topline"><span><?= t('Create tag') ?></span><b>TAG / NEW</b></div>
            <form method="post" action="<?= e(repo_url($repository, 'tags')) ?>" class="stack-form">
                <?= csrf_field() ?>
                <label><span><?= t('Tag name') ?> <i class="required">*</i></span>
                    <input type="text" name="tag" required maxlength="100" placeholder="v0.5.0">
                </label>
                <label><span><?= t('Target branch') ?></span>
                    <select name="branch">
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= e($branch) ?>" <?= $branch === $defaultBranch ? 'selected' : '' ?>><?= e($branch) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit"><?= t('Create tag') ?> →</button>
                    <a class="btn btn-ghost" href="<?= e(repo_url($repository, 'releases/new')) ?>"><?= t('Publish release') ?></a>
                </div>
            </form>
        </section>
    <?php endif; ?>
</div>
