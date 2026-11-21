<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repository-body">
    <div class="repo-toolbar">
        <div class="breadcrumbs">
            <a href="<?= e(repo_url($repository, 'actions')) ?>"><?= t('Actions') ?></a>
            <span>/</span>
            <span><?= t('Run') ?> #<?= (int) $run['id'] ?></span>
        </div>
        <span class="run-status run-status-<?= e($run['status']) ?>"><?= e($run['status']) ?></span>
    </div>

    <section class="tech-panel">
        <div class="panel-topline"><span><?= e($run['workflow_name']) ?></span><b>RUN / #<?= (int) $run['id'] ?></b></div>
        <div class="workflow-meta run-meta">
            <div><span class="wm-label"><?= t('Branch') ?>:</span> <code><?= e($run['branch']) ?></code></div>
            <div><span class="wm-label"><?= t('Commit') ?>:</span> <code class="sha"><?= e($run['commit_sha']) ?></code></div>
            <div><span class="wm-label"><?= t('Trigger') ?>:</span> <?= e($run['trigger_type']) ?><?php if ($run['triggered_by_username']): ?> by <b><?= e($run['triggered_by_username']) ?></b><?php endif; ?></div>
            <div><span class="wm-label"><?= t('Started') ?>:</span> <?= e($run['started_at'] ?: '—') ?></div>
            <div><span class="wm-label"><?= t('Finished') ?>:</span> <?= e($run['finished_at'] ?: '—') ?></div>
            <?php if ($run['error']): ?><div class="run-error"><span class="wm-label"><?= t('Error') ?>:</span> <?= e($run['error']) ?></div><?php endif; ?>
        </div>
    </section>

    <section class="tech-panel">
        <div class="panel-topline"><span><?= t('Log') ?></span><b>STDOUT</b></div>
        <pre class="workflow-log"><?= e($run['log'] ?: t('(empty log)')) ?></pre>
    </section>
</div>
