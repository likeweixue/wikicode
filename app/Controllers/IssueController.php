<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Services\RepositoryService;

final class IssueController
{
    public function index(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo);
        $status = ($_GET['status'] ?? 'open') === 'closed' ? 'closed' : 'open';
        $issues = Database::fetchAll(
            'SELECT i.*, u.username,
                (SELECT COUNT(*) FROM issue_comments c WHERE c.issue_id = i.id) AS comment_count
             FROM issues i JOIN users u ON u.id = i.author_id
             WHERE i.repository_id = ? AND i.status = ? ORDER BY i.updated_at DESC LIMIT 200',
            [$repository['id'], $status]
        );
        $counts = Database::fetch(
            'SELECT SUM(status = \'open\') AS open_count, SUM(status = \'closed\') AS closed_count FROM issues WHERE repository_id = ?',
            [$repository['id']]
        );
        View::render('issues/index', [
            'title' => t('Issues') . ' · ' . $repository['name'],
            'repository' => $repository,
            'activeTab' => 'issues',
            'issues' => $issues,
            'status' => $status,
            'counts' => ['open' => (int) ($counts['open_count'] ?? 0), 'closed' => (int) ($counts['closed_count'] ?? 0)],
        ]);
    }

    public function create(string $owner, string $repo): void
    {
        Auth::requireUser();
        $repository = $this->repository($owner, $repo);
        View::render('issues/create', [
            'title' => t('New issue') . ' · ' . $repository['name'],
            'repository' => $repository,
            'activeTab' => 'issues',
        ]);
    }

    public function store(string $owner, string $repo): void
    {
        Auth::requireUser();
        $repository = $this->repository($owner, $repo);
        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($title === '' || mb_strlen($title) > 200 || mb_strlen($body) > 100000) {
            flash('error', t('Please provide a valid title and keep the description under 100,000 characters.'));
            redirect(repo_url($repository, 'issues/new'));
        }

        $number = Database::transaction(function () use ($repository, $title, $body): int {
            $row = Database::fetch('SELECT issue_seq FROM repositories WHERE id = ? FOR UPDATE', [$repository['id']]);
            $number = (int) $row['issue_seq'] + 1;
            Database::execute('UPDATE repositories SET issue_seq = ? WHERE id = ?', [$number, $repository['id']]);
            Database::execute(
                'INSERT INTO issues (repository_id, number, author_id, title, body) VALUES (?, ?, ?, ?, ?)',
                [$repository['id'], $number, Auth::id(), $title, $body]
            );
            return $number;
        });
        flash('success', t('Issue created. The discussion is now open.'));
        redirect(repo_url($repository, 'issues/' . $number));
    }

    public function show(string $owner, string $repo, string $number): void
    {
        $repository = $this->repository($owner, $repo);
        $issue = $this->issue($repository, (int) $number);
        $comments = Database::fetchAll(
            'SELECT c.*, u.username FROM issue_comments c JOIN users u ON u.id = c.author_id WHERE c.issue_id = ? ORDER BY c.created_at ASC',
            [$issue['id']]
        );
        View::render('issues/show', [
            'title' => $issue['title'] . ' · ' . $repository['name'],
            'repository' => $repository,
            'activeTab' => 'issues',
            'issue' => $issue,
            'comments' => $comments,
        ]);
    }

    public function comment(string $owner, string $repo, string $number): void
    {
        Auth::requireUser();
        $repository = $this->repository($owner, $repo);
        $issue = $this->issue($repository, (int) $number);
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body === '' || mb_strlen($body) > 50000) {
            flash('error', t('Comment cannot be empty and must be under 50,000 characters.'));
        } else {
            Database::execute('INSERT INTO issue_comments (issue_id, author_id, body) VALUES (?, ?, ?)', [$issue['id'], Auth::id(), $body]);
            Database::execute('UPDATE issues SET updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$issue['id']]);
            flash('success', t('Comment added to the discussion.'));
        }
        redirect(repo_url($repository, 'issues/' . $issue['number']));
    }

    public function toggle(string $owner, string $repo, string $number): void
    {
        Auth::requireUser();
        $repository = $this->repository($owner, $repo);
        $issue = $this->issue($repository, (int) $number);
        if (!Auth::canWrite($repository) && Auth::id() !== (int) $issue['author_id']) {
            abort(403);
        }
        $open = $issue['status'] === 'closed';
        Database::execute(
            'UPDATE issues SET status = ?, closed_at = ? WHERE id = ?',
            [$open ? 'open' : 'closed', $open ? null : date('Y-m-d H:i:s'), $issue['id']]
        );
        flash('success', $open ? t('Issue reopened.') : t('Issue closed.'));
        redirect(repo_url($repository, 'issues/' . $issue['number']));
    }

    private function repository(string $owner, string $repo): array
    {
        $repository = RepositoryService::find($owner, $repo);
        if (!$repository) {
            abort(404);
        }
        if (!Auth::canRead($repository)) {
            abort(Auth::check() ? 403 : 404);
        }
        return $repository;
    }

    private function issue(array $repository, int $number): array
    {
        $issue = Database::fetch(
            'SELECT i.*, u.username FROM issues i JOIN users u ON u.id = i.author_id WHERE i.repository_id = ? AND i.number = ?',
            [$repository['id'], $number]
        );
        if (!$issue) {
            abort(404);
        }
        return $issue;
    }
}
