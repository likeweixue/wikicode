<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Setting;
use App\Core\View;
use App\Services\MailService;
use App\Services\RepositoryService;
use Throwable;

final class AdminController
{
    public function dashboard(): void
    {
        Auth::requireAdmin();
        $stats = Database::fetch(
            'SELECT
                (SELECT COUNT(*) FROM users) AS users,
                (SELECT COUNT(*) FROM repositories) AS repositories,
                (SELECT COUNT(*) FROM issues WHERE status = \'open\') AS open_issues,
                (SELECT COUNT(*) FROM pull_requests WHERE status = \'open\') AS open_pulls'
        );
        $repositories = Database::fetchAll(
            'SELECT r.*, u.username FROM repositories r
             JOIN users u ON u.id = r.owner_id
             ORDER BY r.updated_at DESC, r.id DESC LIMIT 6'
        );
        View::render('admin/dashboard', [
            'title' => t('Administration'),
            'active' => 'dashboard',
            'stats' => $stats,
            'repositories' => $repositories,
        ]);
    }

    public function users(): void
    {
        Auth::requireAdmin();
        $users = Database::fetchAll(
            'SELECT u.*, (SELECT COUNT(*) FROM repositories r WHERE r.owner_id = u.id) AS repository_count
             FROM users u ORDER BY u.created_at DESC, u.id DESC'
        );
        View::render('admin/users', [
            'title' => t('User management'),
            'active' => 'users',
            'users' => $users,
        ]);
    }

    public function toggleUser(string $id): void
    {
        Auth::requireAdmin();
        $userId = (int) $id;
        if ($userId === Auth::id()) {
            flash('error', t('You cannot change your own account status.'));
            redirect('/admin/users');
        }
        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [$userId]);
        if (!$user) {
            flash('error', t('User not found.'));
            redirect('/admin/users');
        }
        $active = (int) $user['is_active'] === 1 ? 0 : 1;
        Database::execute('UPDATE users SET is_active = ? WHERE id = ?', [$active, $userId]);
        flash('success', $active === 1 ? t('User account enabled.') : t('User account disabled.'));
        redirect('/admin/users');
    }

    public function userRole(string $id): void
    {
        Auth::requireAdmin();
        $userId = (int) $id;
        if ($userId === Auth::id()) {
            flash('error', t('You cannot change your own role.'));
            redirect('/admin/users');
        }
        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [$userId]);
        if (!$user) {
            flash('error', t('User not found.'));
            redirect('/admin/users');
        }
        $role = $user['role'] === 'admin' ? 'user' : 'admin';
        Database::execute('UPDATE users SET role = ? WHERE id = ?', [$role, $userId]);
        flash('success', $role === 'admin' ? t('User promoted to administrator.') : t('Administrator changed to a regular user.'));
        redirect('/admin/users');
    }

    public function deleteUser(string $id): void
    {
        Auth::requireAdmin();
        $userId = (int) $id;
        if ($userId === Auth::id()) {
            flash('error', t('You cannot delete your own account.'));
            redirect('/admin/users');
        }
        $user = Database::fetch('SELECT id FROM users WHERE id = ?', [$userId]);
        if (!$user) {
            flash('error', t('User not found.'));
            redirect('/admin/users');
        }
        $repositoryIds = Database::fetchAll('SELECT id FROM repositories WHERE owner_id = ?', [$userId]);
        try {
            foreach ($repositoryIds as $repository) {
                RepositoryService::delete((int) $repository['id']);
            }
            Database::execute('DELETE FROM users WHERE id = ?', [$userId]);
            flash('success', t('User and associated repositories deleted.'));
        } catch (Throwable $exception) {
            error_log('User deletion failed: ' . $exception->getMessage());
            flash('error', t('Unable to delete the user and associated repositories. Please try again.'));
        }
        redirect('/admin/users');
    }

    public function repositories(): void
    {
        Auth::requireAdmin();
        $repositories = Database::fetchAll(
            'SELECT r.*, u.username,
                (SELECT COUNT(*) FROM issues i WHERE i.repository_id = r.id AND i.status = \'open\') AS open_issues,
                (SELECT COUNT(*) FROM pull_requests p WHERE p.repository_id = r.id AND p.status = \'open\') AS open_pulls
             FROM repositories r JOIN users u ON u.id = r.owner_id
             ORDER BY r.updated_at DESC, r.id DESC'
        );
        View::render('admin/repositories', [
            'title' => t('Repository management'),
            'active' => 'repositories',
            'repositories' => $repositories,
        ]);
    }

    public function deleteRepository(string $id): void
    {
        Auth::requireAdmin();
        $repositoryId = (int) $id;
        $repository = Database::fetch('SELECT id FROM repositories WHERE id = ?', [$repositoryId]);
        if (!$repository) {
            flash('error', t('Repository not found.'));
            redirect('/admin/repositories');
        }
        try {
            RepositoryService::delete($repositoryId);
            flash('success', t('Repository deleted.'));
        } catch (Throwable $exception) {
            error_log('Repository deletion failed: ' . $exception->getMessage());
            flash('error', t('Unable to delete the repository. Please try again.'));
        }
        redirect('/admin/repositories');
    }

    public function siteSettings(): void
    {
        Auth::requireAdmin();
        View::render('admin/site_settings', [
            'title' => t('Site settings'),
            'active' => 'site',
            'values' => [
                'site_name' => (string) Setting::get('site_name', 'wikicode'),
                'site_description' => (string) Setting::get('site_description', ''),
                'default_language' => (string) Setting::get('default_language', 'zh-CN'),
                'allow_registration' => (string) Setting::get('allow_registration', '1'),
                'explore_hot_title' => (string) Setting::get('explore_hot_title', '热门'),
                'explore_hot_mode' => (string) Setting::get('explore_hot_mode', 'stars'),
                'explore_featured_repos' => (string) Setting::get('explore_featured_repos', ''),
            ],
        ]);
    }

    public function saveSiteSettings(): void
    {
        Auth::requireAdmin();
        $siteName = trim((string) ($_POST['site_name'] ?? ''));
        $description = trim((string) ($_POST['site_description'] ?? ''));
        $language = (string) ($_POST['default_language'] ?? '');
        $registration = isset($_POST['allow_registration']) ? (string) $_POST['allow_registration'] : '0';

        if ($siteName === '' || strlen($siteName) > 100) {
            flash('error', t('Site name is required and must not exceed 100 characters.'));
        } elseif (strlen($description) > 500) {
            flash('error', t('Site description must not exceed 500 characters.'));
        } elseif (!in_array($language, ['zh-CN', 'en'], true)) {
            flash('error', t('Please select a valid default language.'));
        } elseif (!in_array($registration, ['0', '1'], true)) {
            flash('error', t('Invalid registration setting.'));
        } else {
            Setting::set('site_name', $siteName);
            Setting::set('site_description', $description);
            Setting::set('default_language', $language);
            Setting::set('allow_registration', $registration);

            $hotTitle = trim((string) ($_POST['explore_hot_title'] ?? ''));
            $hotMode = ($_POST['explore_hot_mode'] ?? 'stars') === 'featured' ? 'featured' : 'stars';
            $featured = trim((string) ($_POST['explore_featured_repos'] ?? ''));
            if ($hotTitle === '') {
                $hotTitle = t('Hot');
            }
            // 校验 featured 为逗号分隔的正整数
            if ($featured !== '' && !preg_match('/^\d+(,\d+)*$/', $featured)) {
                $featured = '';
            }
            Setting::set('explore_hot_title', $hotTitle);
            Setting::set('explore_hot_mode', $hotMode);
            Setting::set('explore_featured_repos', $featured);

            flash('success', t('Site settings saved.'));
        }
        redirect('/admin/settings/site');
    }

    public function mailSettings(): void
    {
        Auth::requireAdmin();
        View::render('admin/mail_settings', [
            'title' => t('Mail settings'),
            'active' => 'mail',
            'values' => [
                'smtp_host' => (string) Setting::get('smtp_host', ''),
                'smtp_port' => (string) Setting::get('smtp_port', '587'),
                'smtp_encryption' => (string) Setting::get('smtp_encryption', 'tls'),
                'smtp_username' => (string) Setting::get('smtp_username', ''),
                'smtp_from_address' => (string) Setting::get('smtp_from_address', ''),
                'smtp_from_name' => (string) Setting::get('smtp_from_name', Setting::get('site_name', 'wikicode')),
            ],
            'passwordConfigured' => (string) Setting::get('smtp_password', '') !== '',
        ]);
    }

    public function saveMailSettings(): void
    {
        Auth::requireAdmin();
        $host = trim((string) ($_POST['smtp_host'] ?? ''));
        $port = filter_var($_POST['smtp_port'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);
        $encryption = (string) ($_POST['smtp_encryption'] ?? '');
        $username = trim((string) ($_POST['smtp_username'] ?? ''));
        $password = (string) ($_POST['smtp_password'] ?? '');
        $fromAddress = trim((string) ($_POST['smtp_from_address'] ?? ''));
        $fromName = trim((string) ($_POST['smtp_from_name'] ?? ''));
        $validHost = (bool) preg_match('/^(?:\[[0-9A-Fa-f:.]+\]|[A-Za-z0-9](?:[A-Za-z0-9.-]*[A-Za-z0-9])?)$/', $host);

        if (!$validHost || strlen($host) > 255) {
            flash('error', t('Please enter a valid SMTP host.'));
            redirect('/admin/settings/mail');
        }
        if ($port === false || !in_array($encryption, ['none', 'tls', 'ssl'], true)) {
            flash('error', t('Please enter a valid SMTP port and encryption mode.'));
            redirect('/admin/settings/mail');
        }
        if (strlen($username) > 190 || preg_match('/[\r\n]/', $username)) {
            flash('error', t('Please enter a valid SMTP username.'));
            redirect('/admin/settings/mail');
        }
        if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL) || strlen($fromAddress) > 190) {
            flash('error', t('Please enter a valid sender email address.'));
            redirect('/admin/settings/mail');
        }
        if ($fromName === '' || strlen($fromName) > 100 || preg_match('/[\r\n]/', $fromName)) {
            flash('error', t('Sender name is required and must not exceed 100 characters.'));
            redirect('/admin/settings/mail');
        }

        $encryptedPassword = null;
        if ($password !== '') {
            try {
                $encryptedPassword = MailService::encryptPassword($password);
            } catch (Throwable $exception) {
                error_log('SMTP password encryption failed: ' . $exception->getMessage());
                flash('error', t('Unable to encrypt the SMTP password. Check APP_KEY and OpenSSL support.'));
                redirect('/admin/settings/mail');
            }
        }

        Setting::set('smtp_host', $host);
        Setting::set('smtp_port', (string) $port);
        Setting::set('smtp_encryption', $encryption);
        Setting::set('smtp_username', $username);
        if ($encryptedPassword !== null) {
            Setting::set('smtp_password', $encryptedPassword);
        }
        Setting::set('smtp_from_address', $fromAddress);
        Setting::set('smtp_from_name', $fromName);
        flash('success', t('Mail settings saved.'));
        redirect('/admin/settings/mail');
    }

    public function testMail(): void
    {
        Auth::requireAdmin();
        $user = Auth::user();
        try {
            MailService::send(
                (string) $user['email'],
                t('wikicode SMTP test message'),
                t('Your SMTP settings are working. This test message was sent from the wikicode administration panel.')
            );
            flash('success', t('Test mail sent to :email.', ['email' => $user['email']]));
        } catch (Throwable $exception) {
            flash('error', t('Unable to send test mail: :message', ['message' => $exception->getMessage()]));
        }
        redirect('/admin/settings/mail');
    }
}
