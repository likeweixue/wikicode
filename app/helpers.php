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

/**
 * 将 Markdown 文本拆成行，并为 1–3 级标题生成稳定且唯一的锚点 ID。
 *
 * @return list<array{type: string, level?: int, text: string, id?: string}>
 */
function markdown_parse(string $text): array
{
    $lines = [];
    $used = [];
    foreach (preg_split('/\R/', $text) as $line) {
        if (preg_match('/^(#{1,3})\s+(.+?)\s*#*\s*$/', $line, $matches)) {
            $title = trim($matches[2]);
            $base = markdown_slug($title);
            $id = $base;
            $suffix = 2;
            while (isset($used[$id])) {
                $id = $base . '-' . $suffix++;
            }
            $used[$id] = true;
            $lines[] = ['type' => 'heading', 'level' => strlen($matches[1]), 'text' => $title, 'id' => $id];
        } else {
            $lines[] = ['type' => 'text', 'text' => $line];
        }
    }
    return $lines;
}

function markdown_slug(string $text): string
{
    $slug = mb_strtolower(trim(strip_tags($text)), 'UTF-8');
    $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug) ?? '';
    $slug = trim($slug, '-');
    return $slug !== '' ? $slug : 'section';
}

/**
 * 提取 Markdown 标题，用于生成目录。
 *
 * @return list<array{level: int, text: string, id: string}>
 */
function markdown_toc(string $text): array
{
    $toc = [];
    foreach (markdown_parse($text) as $line) {
        if ($line['type'] === 'heading') {
            $toc[] = ['level' => $line['level'], 'text' => $line['text'], 'id' => $line['id']];
        }
    }
    return $toc;
}

function render_markdown(string $text): string
{
    $out = [];
    foreach (markdown_parse($text) as $line) {
        if ($line['type'] === 'heading') {
            $level = $line['level'];
            $out[] = '<h' . $level . ' id="' . e($line['id']) . '">' . e($line['text']) . '</h' . $level . '>';
        } else {
            $out[] = e($line['text']);
        }
    }
    $html = implode("\n", $out);
    $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html) ?? $html;
    $html = preg_replace('/`([^`\n]+)`/', '<code>$1</code>', $html) ?? $html;
    return nl2br($html, false);
}

function ngettext(string $singular, string $plural, int $count): string
{
    return $count === 1 ? $singular : $plural;
}

function ngettext_placeholder(string $singular, string $plural, int $count, string $param = ':count'): string
{
    $template = $count === 1 ? $singular : $plural;
    return str_replace($param, (string) $count, $template);
}
