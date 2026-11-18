<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\I18n;
use App\Core\View;

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $path = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

require __DIR__ . '/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('wikicode_session');
    session_start();
}

date_default_timezone_set('UTC');

I18n::boot();
View::share('currentUser', Auth::user());
View::share('currentLocale', I18n::locale());
View::share('currentTheme', $_SESSION['theme'] ?? (Auth::user()['theme'] ?? 'light'));
