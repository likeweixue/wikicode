<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\I18n;
use App\Core\Setting;
use App\Core\View;

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function t(string $key, array $replace = []): string
{
    return I18n::get($key, $replace);
}

function url(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
        $path = '/';
    }
    header('Location: ' . $path);
    exit;
}

function flash(string $type, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$type] = $message;
        return null;
    }
    $value = $_SESSION['_flash'][$type] ?? null;
    unset($_SESSION['_flash'][$type]);
    return $value;
}

function csrf_field(): string
{
    return Csrf::field();
}

function current_user(): ?array
{
    return Auth::user();
}

function abort(int $code): void
{
    http_response_code($code);
    $titles = [403 => t('Access denied'), 404 => t('Page not found'), 419 => t('Page expired'), 500 => t('System error')];
    $view = in_array($code, [403, 404, 419, 500], true) ? (string) $code : '404';
    View::render('errors/' . $view, ['title' => $titles[$code] ?? t('Page not found')]);
    exit;
}

function setting(string $key, mixed $default = null): mixed
{
    return Setting::get($key, $default);
}

function app_url(string $path = ''): string
{
    return rtrim(getenv('APP_URL') ?: 'http://localhost:8088', '/') . '/' . ltrim($path, '/');
}

function repo_url(array $repository, string $suffix = ''): string
{
    return '/' . rawurlencode($repository['username']) . '/' . rawurlencode($repository['name'])
        . ($suffix !== '' ? '/' . ltrim($suffix, '/') : '');
}

function format_date(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $timestamp = strtotime($date . (str_ends_with($date, 'Z') ? '' : ' UTC'));
    return date(I18n::locale() === 'en' ? 'M j, Y H:i' : 'Y年m月d日 H:i', $timestamp);
}

function short_sha(string $sha): string
{
    return substr($sha, 0, 7);
}

function active_class(bool $active): string
{
    return $active ? ' is-active' : '';
}

function render_markdown(string $text): string
{
    $escaped = e($text);
    $escaped = preg_replace('/^###\s+(.+)$/m', '<h3>$1</h3>', $escaped);
    $escaped = preg_replace('/^##\s+(.+)$/m', '<h2>$1</h2>', $escaped);
    $escaped = preg_replace('/^#\s+(.+)$/m', '<h1>$1</h1>', $escaped);
    $escaped = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped);
    $escaped = preg_replace('/`([^`\n]+)`/', '<code>$1</code>', $escaped);
    return nl2br($escaped, false);
}
