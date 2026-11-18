<section class="error-page tech-panel">
    <span class="error-code">403 / ACCESS DENIED</span>
    <h1><?= e($title ?? t('Access denied')) ?></h1>
    <p><?= t('Your current identity does not have permission to access this resource.') ?></p>
    <a class="btn btn-primary" href="/"><?= t('Return home') ?> →</a>
</section>
