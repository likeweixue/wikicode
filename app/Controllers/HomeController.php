<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class HomeController
{
    public function index(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }
        $repositories = Database::fetchAll(
            'SELECT r.*, u.username FROM repositories r JOIN users u ON u.id = r.owner_id
             WHERE r.visibility = \'public\' ORDER BY r.updated_at DESC LIMIT 6'
        );
        $stats = [
            'repositories' => (int) Database::fetch('SELECT COUNT(*) AS count FROM repositories WHERE visibility = \'public\'')['count'],
            'users' => (int) Database::fetch('SELECT COUNT(*) AS count FROM users WHERE is_active = 1')['count'],
            'pull_requests' => (int) Database::fetch('SELECT COUNT(*) AS count FROM pull_requests')['count'],
        ];
        View::render('home', [
            'title' => setting('site_name', 'wikicode') . ' — ' . t('Build code together'),
            'repositories' => $repositories,
            'stats' => $stats,
        ]);
    }
}
