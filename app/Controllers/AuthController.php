<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\I18n;
use App\Core\View;
use Throwable;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }
        View::render('auth/login', ['title' => t('Sign in')]);
    }

    public function login(): void
    {
        $login = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($login === '' || $password === '' || !Auth::attempt($login, $password)) {
            View::render('auth/login', [
                'title' => t('Sign in'),
                'error' => t('Invalid credentials or the account is disabled.'),
                'loginValue' => $login,
            ]);
            return;
        }
        flash('success', t('Identity verified. Welcome back.'));
        redirect('/dashboard');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }
        if (setting('allow_registration', '1') !== '1') {
            http_response_code(403);
            View::render('errors/403', ['title' => t('Registration is closed')]);
            return;
        }
        View::render('auth/register', ['title' => t('Create account')]);
    }

    public function register(): void
    {
        if (setting('allow_registration', '1') !== '1') {
            Auth::requireUser();
            redirect('/dashboard');
        }
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');
        $error = null;

        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{1,31}$/', $username)) {
            $error = t('Username must be 2–32 characters and contain only letters, numbers, hyphens or underscores.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $error = t('Please enter a valid email address.');
        } elseif (strlen($password) < 12) {
            $error = t('Password must contain at least 12 characters.');
        } elseif ($password !== $confirmation) {
            $error = t('Password confirmation does not match.');
        } elseif (Database::fetch('SELECT id FROM users WHERE username = ? OR email = ?', [$username, $email])) {
            $error = t('Username or email already exists.');
        }

        if ($error) {
            View::render('auth/register', [
                'title' => t('Create account'),
                'error' => $error,
                'values' => ['username' => $username, 'email' => $email],
            ]);
            return;
        }

        try {
            Database::execute(
                'INSERT INTO users (username, email, password, locale, theme) VALUES (?, ?, ?, ?, ?)',
                [$username, $email, password_hash($password, PASSWORD_DEFAULT), I18n::locale(), $_SESSION['theme'] ?? 'light']
            );
        } catch (Throwable) {
            View::render('auth/register', [
                'title' => t('Create account'),
                'error' => t('Unable to create this account. Please try again.'),
                'values' => ['username' => $username, 'email' => $email],
            ]);
            return;
        }

        Auth::login((int) Database::pdo()->lastInsertId());
        flash('success', t('Your wikicode identity is ready.'));
        redirect('/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        flash('success', t('Signed out securely.'));
        redirect('/');
    }

    public function locale(): void
    {
        $locale = ($_POST['locale'] ?? '') === 'en' ? 'en' : 'zh-CN';
        I18n::setLocale($locale);
        if (Auth::check()) {
            Database::execute('UPDATE users SET locale = ? WHERE id = ?', [$locale, Auth::id()]);
        }
        $this->back();
    }

    public function theme(): void
    {
        $theme = ($_POST['theme'] ?? '') === 'dark' ? 'dark' : 'light';
        $_SESSION['theme'] = $theme;
        if (Auth::check()) {
            Database::execute('UPDATE users SET theme = ? WHERE id = ?', [$theme, Auth::id()]);
        }
        $this->back();
    }

    private function back(): void
    {
        $return = (string) ($_POST['return'] ?? '/');
        if (!str_starts_with($return, '/') || str_starts_with($return, '//') || preg_match('/[\r\n]/', $return)) {
            $return = '/';
        }
        redirect($return);
    }
}
