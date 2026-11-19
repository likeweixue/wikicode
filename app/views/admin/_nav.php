<?php $active = $active ?? ''; ?>
<aside class="admin-sidebar">
    <div class="admin-sidebar-header">
        <span>CONTROL / ADMIN</span>
        <strong><?= e(t('Administration')) ?></strong>
    </div>
    <nav aria-label="<?= e(t('Administration navigation')) ?>">
        <a class="<?= $active === 'dashboard' ? 'is-active' : '' ?>" href="/admin"><?= e(t('Overview')) ?></a>
        <a class="<?= $active === 'users' ? 'is-active' : '' ?>" href="/admin/users"><?= e(t('Users')) ?></a>
        <a class="<?= $active === 'repositories' ? 'is-active' : '' ?>" href="/admin/repositories"><?= e(t('Repositories')) ?></a>
        <a class="<?= $active === 'categories' ? 'is-active' : '' ?>" href="/admin/categories"><?= e(t('Categories')) ?></a>
        <a class="<?= $active === 'community' ? 'is-active' : '' ?>" href="/admin/community"><?= e(t('Community')) ?></a>
        <a class="<?= $active === 'navigation' ? 'is-active' : '' ?>" href="/admin/navigation"><?= e(t('Navigation')) ?></a>
        <a class="<?= $active === 'deploy-tokens' ? 'is-active' : '' ?>" href="/admin/deploy-tokens"><?= e(t('Deploy tokens')) ?></a>
        <a class="<?= $active === 'site' ? 'is-active' : '' ?>" href="/admin/settings/site"><?= e(t('Site settings')) ?></a>
        <a class="<?= $active === 'mail' ? 'is-active' : '' ?>" href="/admin/settings/mail"><?= e(t('Mail settings')) ?></a>
    </nav>
</aside>
