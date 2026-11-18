<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }
        self::$loaded = true;
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            return null;
        }
        $user = Database::fetch('SELECT * FROM users WHERE id = ? AND is_active = 1', [(int) $id]);
        if (!$user) {
            unset($_SESSION['user_id']);
            return null;
        }
        self::$user = $user;
        return $user;
    }

    public static function id(): ?int
    {
        return self::user() ? (int) self::user()['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        return self::user() !== null && self::user()['role'] === 'admin';
    }

    public static function attempt(string $login, string $password): bool
    {
        $user = Database::fetch(
            'SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1',
            [$login, $login]
        );
        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['locale'] = $user['locale'];
        $_SESSION['theme'] = $user['theme'];
        self::$user = $user;
        self::$loaded = true;
        return true;
    }

    public static function login(int $id): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $id;
        self::$loaded = false;
        self::$user = null;
    }

    public static function logout(): void
    {
        self::$user = null;
        self::$loaded = false;
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
    }

    public static function requireUser(): void
    {
        if (!self::check()) {
            flash('error', t('Please sign in to continue.'));
            redirect('/login');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireUser();
        if (!self::isAdmin()) {
            http_response_code(403);
            View::render('errors/403', ['title' => t('Access denied')]);
            exit;
        }
    }

    public static function canRead(array $repository): bool
    {
        return $repository['visibility'] === 'public'
            || self::isAdmin()
            || self::id() === (int) $repository['owner_id'];
    }

    public static function canWrite(array $repository): bool
    {
        return self::isAdmin() || self::id() === (int) $repository['owner_id'];
    }
}
