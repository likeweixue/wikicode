<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repository-body">
    <div class="repo-toolbar">
        <div class="breadcrumbs">
            <a href="<?= e(repo_url($repository, 'actions')) ?>"><?= t('Actions') ?></a>
            <span>/</span>
            <span><?= e($branch) ?></span>
        </div>
        <span class="section-code">WORKFLOW / <?= $workflow ? e($workflow['name']) : 'NONE' ?></span>
    </div>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="form-error"><?= e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>

    <section class="tech-panel">
        <div class="panel-topline"><span><?= t('Workflow') ?></span><b>.wikicode.yml</b></div>
        <?php if ($parseError): ?>
            <div class="workflow-empty">
                <p><b><?= t('Workflow file parse error') ?>:</b> <?= e($parseError) ?></p>
            </div>
        <?php elseif (!$workflow): ?>
            <div class="workflow-empty">
                <p><?= t('No') ?> <code>.wikicode.yml</code> <?= t('found in branch') ?> <code><?= e($branch) ?></code>.</p>
                <p class="workflow-hint"><?= t('Create one in your repository root to enable CI. Example:') ?></p>
                <pre class="workflow-example">name: CI
on: [manual, push]
jobs:
  build:
    image: alpine:3.20
    steps:
      - run: echo "Hello from wikicode!"
      - run: ls -la</pre>
            </div>
        <?php else: ?>
            <div class="workflow-meta">
                <div><span class="wm-label"><?= t('Name') ?>:</span> <b><?= e($workflow['name']) ?></b></div>
                <div><span class="wm-label"><?= t('Triggers') ?>:</span> <?php foreach ($workflow['on'] as $trigger): ?><code><?= e($trigger) ?></code><?php endforeach; ?></div>
                <div><span class="wm-label"><?= t('Jobs') ?>:</span> <?= count($workflow['jobs']) ?> (<?= e(implode(', ', array_keys($workflow['jobs']))) ?>)</div>
            </div>
            <?php if ($canWrite): ?>
                <form method="post" action="<?= e(repo_url($repository, 'actions/trigger')) ?>" class="workflow-trigger-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="branch" value="<?= e($branch) ?>">
                    <button class="btn btn-primary" type="submit">▶ <?= t('Run workflow') ?></button>
                    <span class="workflow-trigger-hint"><?= t('Runs synchronously; you will be redirected to the run detail page.') ?></span>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <section class="tech-panel">
        <div class="panel-topline"><span><?= t('Recent runs') ?></span><b><?= count($runs) ?></b></div>
        <?php if (empty($runs)): ?>
            <div class="workflow-empty"><p><?= t('No runs yet.') ?></p></div>
        <?php else: ?>
            <table class="workflow-runs-table">
                <thead><tr>
                    <th>#</th>
                    <th><?= t('Workflow') ?></th>
                    <th><?= t('Branch') ?></th>
                    <th><?= t('Commit') ?></th>
                    <th><?= t('Trigger') ?></th>
                    <th><?= t('Status') ?></th>
                    <th><?= t('Started') ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($runs as $run): ?>
                    <tr>
                        <td><a href="<?= e(repo_url($repository, 'actions/runs/' . $run['id'])) ?>">#<?= (int) $run['id'] ?></a></td>
                        <td><?= e($run['workflow_name']) ?></td>
                        <td><code><?= e($run['branch']) ?></code></td>
                        <td><code class="sha"><?= e(substr($run['commit_sha'], 0, 7)) ?></code></td>
                        <td><?= e($run['trigger_type']) ?><?php if ($run['triggered_by_username']): ?> <span class="muted">by <?= e($run['triggered_by_username']) ?></span><?php endif; ?></td>
                        <td><span class="run-status run-status-<?= e($run['status']) ?>"><?= e($run['status']) ?></span></td>
                        <td><?= e($run['started_at'] ?: $run['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>
