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
        <a class="<?= $active === 'site' ? 'is-active' : '' ?>" href="/admin/settings/site"><?= e(t('Site settings')) ?></a>
        <a class="<?= $active === 'mail' ? 'is-active' : '' ?>" href="/admin/settings/mail"><?= e(t('Mail settings')) ?></a>
    </nav>
</aside>
