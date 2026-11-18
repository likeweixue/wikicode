<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repository-body">
    <section class="form-panel tech-panel">
        <div class="panel-topline"><span><?= t('New issue') ?></span><b>DISCUSSION / NEW</b></div>
        <form method="post" action="<?= e(repo_url($repository, 'issues')) ?>" class="stack-form">
            <?= csrf_field() ?>
            <label><span><?= t('Title') ?> <i class="required">*</i></span><input type="text" name="title" required maxlength="200" autofocus placeholder="<?= t('A clear, specific summary') ?>"></label>
            <label><span><?= t('Description') ?></span><textarea name="body" rows="10" placeholder="<?= t('Describe the context, expected result and steps to reproduce…') ?>"></textarea><small><?= t('Basic Markdown headings, bold and inline code are supported.') ?></small></label>
            <div class="form-actions"><button class="btn btn-primary" type="submit"><?= t('Create issue') ?> →</button><a class="btn btn-ghost" href="<?= e(repo_url($repository, 'issues')) ?>"><?= t('Cancel') ?></a></div>
        </form>
    </section>
</div>
