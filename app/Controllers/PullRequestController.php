<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Services\PullRequestService;
use App\Services\RepositoryService;
use DomainException;
use Throwable;

final class PullRequestController
{
    private PullRequestService $service;

    public function __construct(?PullRequestService $service = null)
    {
        $this->service = $service ?? new PullRequestService();
    }

    public function index(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo);
        $requested = $_GET['status'] ?? 'open';
        $status = in_array($requested, ['closed', 'merged'], true) ? $requested : 'open';
        $pullRequests = Database::fetchAll(
            'SELECT p.*, u.username FROM pull_requests p JOIN users u ON u.id = p.author_id
             WHERE p.repository_id = ? AND p.status = ? ORDER BY p.updated_at DESC LIMIT 200',
            [$repository['id'], $status]
        );
        $rows = Database::fetchAll(
            'SELECT status, COUNT(*) AS count FROM pull_requests WHERE repository_id = ? GROUP BY status',
            [$repository['id']]
        );
        $counts = ['open' => 0, 'closed' => 0, 'merged' => 0];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }
        View::render('pulls/index', [
            'title' => t('Pull requests') . ' · ' . $repository['name'],
            'repository' => $repository,
            'activeTab' => 'pulls',
            'pullRequests' => $pullRequests,
            'status' => $status,
            'counts' => $counts,
        ]);
    }

    public function create(string $owner, string $repo): void
    {
        Auth::requireUser();
        $repository = $this->repository($owner, $repo);
        $branches = $this->service->branches((int) $repository['id']);
        View::render('pulls/create', [
            'title' => t('New pull request') . ' · ' . $repository['name'],
            'repository' => $repository,
            'activeTab' => 'pulls',
            'branches' => $branches,
        ]);
    }

    public function store(string $owner, string $repo): void
    {
        Auth::requireUser();
        $repository = $this->repository($owner, $repo);
        $base = (string) ($_POST['base_branch'] ?? $repository['default_branch']);
        $head = (string) ($_POST['head_branch'] ?? '');
        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $baseSha = $this->service->resolveBranch((int) $repository['id'], $base);
        $headSha = $this->service->resolveBranch((int) $repository['id'], $head);

        if ($base === $head || !$baseSha || !$headSha) {
            flash('error', t('Select two different, valid branches.'));
            redirect(repo_url($repository, 'pulls/new'));
        }
        if (!$this->service->hasChanges((int) $repository['id'], $baseSha, $headSha)) {
            flash('error', t('The selected head branch has no new commits to merge.'));
            redirect(repo_url($repository, 'pulls/new'));
        }
        if (Database::fetch(
            'SELECT id FROM pull_requests WHERE repository_id = ? AND base_branch = ? AND head_branch = ? AND status = \'open\'',
            [$repository['id'], $base, $head]
        )) {
            flash('error', t('An open pull request already exists for these branches.'));
            redirect(repo_url($repository, 'pulls'));
        }
        if (mb_strlen($title) > 200 || mb_strlen($body) > 100000) {
            flash('error', t('Title or description is too long.'));
            redirect(repo_url($repository, 'pulls/new'));
        }
        if ($title === '') {
            $title = $head . ' → ' . $base;
        }

        $number = Database::transaction(function () use ($repository, $base, $head, $title, $body, $baseSha, $headSha): int {
            $row = Database::fetch('SELECT pr_seq FROM repositories WHERE id = ? FOR UPDATE', [$repository['id']]);
            $number = (int) $row['pr_seq'] + 1;
            Database::execute('UPDATE repositories SET pr_seq = ? WHERE id = ?', [$number, $repository['id']]);
            Database::execute(
                'INSERT INTO pull_requests (repository_id, number, author_id, title, body, base_branch, head_branch, base_sha, head_sha)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$repository['id'], $number, Auth::id(), $title, $body, $base, $head, $baseSha, $headSha]
            );
            return $number;
        });
        flash('success', t('Pull request created. Changes are ready for review.'));
        redirect(repo_url($repository, 'pulls/' . $number));
    }

    public function show(string $owner, string $repo, string $number): void
    {
        $repository = $this->repository($owner, $repo);
        $pullRequest = $this->pullRequest($repository, (int) $number);
        if ($pullRequest['status'] === 'open') {
            $baseSha = $this->service->resolveBranch((int) $repository['id'], $pullRequest['base_branch']);
            $headSha = $this->service->resolveBranch((int) $repository['id'], $pullRequest['head_branch']);
            if ($baseSha && $headSha && ($baseSha !== $pullRequest['base_sha'] || $headSha !== $pullRequest['head_sha'])) {
                $pullRequest['base_sha'] = $baseSha;
                $pullRequest['head_sha'] = $headSha;
                Database::execute('UPDATE pull_requests SET base_sha = ?, head_sha = ? WHERE id = ?', [$baseSha, $headSha, $pullRequest['id']]);
            }
        }
        $diff = ['content' => '', 'truncated' => false];
        $commits = [];
        $hasChanges = false;
        $compareError = null;
        try {
            $diff = $this->service->diff((int) $repository['id'], $pullRequest['base_sha'], $pullRequest['head_sha']);
            $commits = $this->service->commits((int) $repository['id'], $pullRequest['base_sha'], $pullRequest['head_sha']);
            $hasChanges = $this->service->hasChanges((int) $repository['id'], $pullRequest['base_sha'], $pullRequest['head_sha']);
        } catch (Throwable) {
            $compareError = t('The saved commits can no longer be compared. Restore the branches or close this request.');
        }
        View::render('pulls/show', [
            'title' => $pullRequest['title'] . ' · ' . $repository['name'],
            'repository' => $repository,
            'activeTab' => 'pulls',
            'pullRequest' => $pullRequest,
            'diff' => $diff,
            'commits' => $commits,
            'hasChanges' => $hasChanges,
            'compareError' => $compareError,
        ]);
    }

    public function merge(string $owner, string $repo, string $number): void
    {
        Auth::requireUser();
        $repository = $this->repository($owner, $repo);
        if (!Auth::canWrite($repository)) {
            abort(403);
        }
        $pullRequest = $this->pullRequest($repository, (int) $number);
        try {
            $commit = $this->service->merge(
                $repository,
                $pullRequest,
                (string) ($_POST['base_sha'] ?? ''),
                (string) ($_POST['head_sha'] ?? '')
            );
            flash('success', t('Pull request merged at :sha.', ['sha' => short_sha($commit)]));
        } catch (DomainException $exception) {
            flash('error', t($exception->getMessage()));
        } catch (Throwable $exception) {
            error_log((string) $exception);
            flash('error', t('Merge failed. The target branch was not changed.'));
        }
        redirect(repo_url($repository, 'pulls/' . $number));
    }

    public function toggle(string $owner, string $repo, string $number): void
    {
        Auth::requireUser();
        $repository = $this->repository($owner, $repo);
        $pullRequest = $this->pullRequest($repository, (int) $number);
        if (!Auth::canWrite($repository) && Auth::id() !== (int) $pullRequest['author_id']) {
            abort(403);
        }
        if ($pullRequest['status'] === 'merged') {
            flash('error', t('A merged pull request cannot be reopened.'));
        } else {
            $status = $pullRequest['status'] === 'open' ? 'closed' : 'open';
            Database::execute('UPDATE pull_requests SET status = ? WHERE id = ?', [$status, $pullRequest['id']]);
            flash('success', $status === 'open' ? t('Pull request reopened.') : t('Pull request closed.'));
        }
        redirect(repo_url($repository, 'pulls/' . $number));
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

    private function pullRequest(array $repository, int $number): array
    {
        $pullRequest = Database::fetch(
            'SELECT p.*, u.username FROM pull_requests p JOIN users u ON u.id = p.author_id WHERE p.repository_id = ? AND p.number = ?',
            [$repository['id'], $number]
        );
        if (!$pullRequest) {
            abort(404);
        }
        return $pullRequest;
    }
}
