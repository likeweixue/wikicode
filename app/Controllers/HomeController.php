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
        $repositories = Database::fetchAll(
            'SELECT r.*, u.username,
                (SELECT COUNT(*) FROM issues i WHERE i.repository_id = r.id AND i.status = \'open\') AS open_issues
             FROM repositories r JOIN users u ON u.id = r.owner_id
             WHERE r.visibility = \'public\' ORDER BY r.updated_at DESC LIMIT 60'
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
