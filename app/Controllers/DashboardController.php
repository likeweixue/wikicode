<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class DashboardController
{
    public function index(): void
    {
        Auth::requireUser();
        $repositories = Database::fetchAll(
            'SELECT r.*, u.username,
                (SELECT COUNT(*) FROM issues i WHERE i.repository_id = r.id AND i.status = \'open\') AS open_issues,
                (SELECT COUNT(*) FROM pull_requests p WHERE p.repository_id = r.id AND p.status = \'open\') AS open_pulls
             FROM repositories r JOIN users u ON u.id = r.owner_id
             WHERE r.owner_id = ? ORDER BY r.updated_at DESC',
            [Auth::id()]
        );
        $publicRepositories = Database::fetchAll(
            'SELECT r.*, u.username FROM repositories r JOIN users u ON u.id = r.owner_id
             WHERE r.visibility = \'public\' AND r.owner_id <> ? ORDER BY r.updated_at DESC LIMIT 5',
            [Auth::id()]
        );
        $view = ($_GET['view'] ?? '') === 'starred' ? 'starred' : 'repositories';
        $starredRepositories = Database::fetchAll(
            'SELECT r.*, u.username,
                (SELECT COUNT(*) FROM issues i WHERE i.repository_id = r.id AND i.status = \'open\') AS open_issues
             FROM stars s
             JOIN repositories r ON r.id = s.repository_id
             JOIN users u ON u.id = r.owner_id
             WHERE s.user_id = ? AND (r.visibility = \'public\' OR r.owner_id = ?)
             ORDER BY s.created_at DESC',
            [Auth::id(), Auth::id()]
        );
        $stats = [
            'repositories' => count($repositories),
            'issues' => array_sum(array_map(static fn(array $repo): int => (int) $repo['open_issues'], $repositories)),
            'pulls' => array_sum(array_map(static fn(array $repo): int => (int) $repo['open_pulls'], $repositories)),
        ];
        View::render('dashboard', [
            'title' => t('Dashboard'),
            'repositories' => $repositories,
            'starredRepositories' => $starredRepositories,
            'view' => $view,
            'publicRepositories' => $publicRepositories,
            'stats' => $stats,
        ]);
    }
}
