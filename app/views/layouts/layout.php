<!doctype html>
<html lang="<?= e($currentLocale) ?>" data-theme="<?= e($currentTheme === 'dark' ? 'dark' : 'light') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title><?= e($title ?? setting('site_name', 'wikicode')) ?></title>
    <link rel="stylesheet" href="/assets/app.css?v=<?= e((string) (@filemtime(__DIR__ . '/../../../public/assets/app.css') ?: 1)) ?>">
    <script src="/assets/app.js?v=<?= e((string) (@filemtime(__DIR__ . '/../../../public/assets/app.js') ?: 1)) ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#main-content"><?= t('Skip to content') ?></a>
<div class="page-grid" aria-hidden="true"></div>
<header class="site-header">
    <div class="shell header-inner">
        <a class="brand" href="/" aria-label="wikicode home">
            <span class="brand-mark" aria-hidden="true"><i></i><b>W</b></span>
            <span class="brand-copy">
                <strong><?= e(setting('site_name', 'wikicode')) ?></strong>
                <small>COLLABORATIVE CODE ARCHIVE</small>
            </span>
        </a>
        <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="main-nav">
            <span></span><span></span><span></span><span class="sr-only"><?= t('Toggle navigation') ?></span>
        </button>
        <nav class="main-nav" id="main-nav" data-nav>
            <?php $navPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH); ?>
            <?php foreach (\App\Services\NavigationService::items() as $navItem): ?>
                <?php $navUrl = rtrim((string) $navItem['url'], '/'); $isActive = $navUrl === '' ? $navPath === '/' : ($navPath === $navUrl || str_starts_with($navPath, $navUrl . '/')); ?>
                <a href="<?= e($navItem['url']) ?>" class="<?= active_class($isActive) ?>"><?= e(\App\Services\NavigationService::label($navItem)) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="header-tools">
            <form method="post" action="/locale" class="compact-form">
                <?= csrf_field() ?>
                <input type="hidden" name="return" value="<?= e(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) ?>">
                <input type="hidden" name="locale" value="<?= $currentLocale === 'en' ? 'zh-CN' : 'en' ?>">
                <button class="text-button" type="submit" title="Language / 语言"><?= $currentLocale === 'en' ? '中文' : 'EN' ?></button>
            </form>
            <form method="post" action="/theme" class="compact-form">
                <?= csrf_field() ?>
                <input type="hidden" name="return" value="<?= e(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) ?>">
                <input type="hidden" name="theme" value="<?= $currentTheme === 'dark' ? 'light' : 'dark' ?>">
                <button class="theme-toggle" type="submit" title="<?= t('Toggle theme') ?>"><span></span><b><?= $currentTheme === 'dark' ? 'DARK' : 'LIGHT' ?></b></button>
            </form>
            <?php if ($currentUser): ?>
                <a class="user-chip" href="/dashboard"><span><?= e(strtoupper(substr($currentUser['username'], 0, 2))) ?></span><?= e($currentUser['username']) ?></a>
                <form method="post" action="/logout" class="compact-form">
                    <?= csrf_field() ?>
                    <button class="text-button muted" type="submit"><?= t('Sign out') ?></button>
                </form>
            <?php else: ?>
                <a class="text-button" href="/login"><?= t('Sign in') ?></a>
                <a class="btn btn-small btn-primary" href="/register"><?= t('Join now') ?></a>
            <?php endif; ?>
        </div>
    </div>
</header>
<div class="system-strip">
    <div class="shell">
        <span><i class="status-dot"></i> WIKICODE NETWORK / ONLINE</span>
        <span>GIT SMART HTTP · PHP 8.3 · MYSQL 8.4</span>
        <span class="strip-coordinate">RHL—<?= date('Y') ?> / <?= strtoupper($currentLocale) ?></span>
    </div>
</div>
<?php if ($message = flash('success')): ?>
    <div class="shell flash flash-success" data-flash><span><?= e($message) ?></span><button type="button" data-dismiss aria-label="Close">×</button></div>
<?php endif; ?>
<?php if ($message = flash('error')): ?>
    <div class="shell flash flash-error" data-flash><span><?= e($message) ?></span><button type="button" data-dismiss aria-label="Close">×</button></div>
<?php endif; ?>
<main class="shell main-content" id="main-content">
    <?= $content ?>
</main>
<footer class="site-footer">
    <div class="shell footer-grid">
        <div>
            <strong>WIKI<span>/</span>CODE</strong>
            <p><?= e(setting('site_description', '一起搭建的代码仓库')) ?></p>
        </div>
        <div class="footer-meta">
            <span>BUILD TOGETHER. SHIP TOGETHER.</span>
            <span>© <?= date('Y') ?> WIKICODE / LOCAL INSTANCE</span>
        </div>
    </div>
</footer>
</body>
</html>
