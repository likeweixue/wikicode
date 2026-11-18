<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repository-body">
    <?php if (count($branches) < 2): ?>
        <div class="empty-state tech-panel"><span class="empty-cross">+</span><h3><?= t('Two branches are required') ?></h3><p><?= t('Push a feature branch with new commits before opening a pull request.') ?></p><a class="btn" href="<?= e(repo_url($repository)) ?>"><?= t('View clone instructions') ?></a></div>
    <?php else: ?>
        <section class="form-panel tech-panel wide">
            <div class="panel-topline"><span><?= t('New pull request') ?></span><b>COMPARE / MERGE</b></div>
            <form method="post" action="<?= e(repo_url($repository, 'pulls')) ?>" class="stack-form">
                <?= csrf_field() ?>
                <div class="form-grid-two">
                    <label><span><?= t('Base branch') ?></span><select name="base_branch"><?php foreach ($branches as $branch): ?><option value="<?= e($branch) ?>" <?= $branch === $repository['default_branch'] ? 'selected' : '' ?>><?= e($branch) ?></option><?php endforeach; ?></select><small><?= t('The branch that receives changes') ?></small></label>
                    <label><span><?= t('Head branch') ?></span><select name="head_branch"><?php foreach ($branches as $branch): ?><option value="<?= e($branch) ?>" <?= $branch !== $repository['default_branch'] ? 'selected' : '' ?>><?= e($branch) ?></option><?php endforeach; ?></select><small><?= t('The branch containing your work') ?></small></label>
                </div>
                <label><span><?= t('Title') ?></span><input type="text" name="title" maxlength="200" placeholder="<?= t('Summarize this proposed change') ?>"></label>
                <label><span><?= t('Description') ?></span><textarea name="body" rows="8" placeholder="<?= t('Explain what changed, why it matters and how to verify it…') ?>"></textarea></label>
                <div class="inline-notice"><?= t('The head branch must contain commits that are not yet in the base branch. Only one open request is allowed per branch pair.') ?></div>
                <div class="form-actions"><button class="btn btn-primary" type="submit"><?= t('Create pull request') ?> →</button><a class="btn btn-ghost" href="<?= e(repo_url($repository, 'pulls')) ?>"><?= t('Cancel') ?></a></div>
            </form>
        </section>
    <?php endif; ?>
</div>
