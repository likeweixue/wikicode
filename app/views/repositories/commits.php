<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repository-body">
    <div class="repo-toolbar"><form class="branch-control" method="get" action="<?= e(repo_url($repository, 'commits')) ?>"><span class="section-code">BRANCH</span><select name="ref" data-auto-submit><?php foreach ($branches as $item): ?><option value="<?= e($item) ?>" <?= $item === $branch ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select></form><span class="section-code">LATEST 50 COMMITS</span></div>
    <div class="commit-list">
        <?php foreach ($commits as $commit): ?><article class="commit-row"><div><a class="issue-title" href="<?= e(repo_url($repository, 'commit/' . $commit['sha'])) ?>"><?= e($commit['subject']) ?></a><small><?= e($commit['author_name']) ?> · <?= e($commit['author_email']) ?></small></div><small><?= e(format_date($commit['author_date'])) ?></small><a class="commit-sha" href="<?= e(repo_url($repository, 'commit/' . $commit['sha'])) ?>"><?= e(short_sha($commit['sha'])) ?></a></article><?php endforeach; ?>
        <?php if (!$commits): ?><div class="empty-state"><p><?= t('No commits on this branch.') ?></p></div><?php endif; ?>
    </div>
</div>
