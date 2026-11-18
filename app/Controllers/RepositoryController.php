<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Services\GitService;
use App\Services\RepositoryService;
use InvalidArgumentException;
use Throwable;

final class RepositoryController
{
    public function create(): void
    {
        Auth::requireUser();
        View::render('repositories/create', ['title' => t('New repository')]);
    }

    public function store(): void
    {
        Auth::requireUser();
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $visibility = ($_POST['visibility'] ?? '') === 'private' ? 'private' : 'public';
        $values = compact('name', 'description', 'visibility');

        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,49}$/', $name) || str_ends_with(strtolower($name), '.git')) {
            View::render('repositories/create', [
                'title' => t('New repository'),
                'error' => t('Use 1–50 letters, numbers, dots, hyphens or underscores; the name cannot end with .git.'),
                'values' => $values,
            ]);
            return;
        }
        if (RepositoryService::find(Auth::user()['username'], $name)) {
            View::render('repositories/create', [
                'title' => t('New repository'),
                'error' => t('You already have a repository with this name.'),
                'values' => $values,
            ]);
            return;
        }

        try {
            $repository = RepositoryService::create((int) Auth::id(), $name, $description, $visibility);
        } catch (Throwable $exception) {
            error_log((string) $exception);
            View::render('repositories/create', [
                'title' => t('New repository'),
                'error' => t('The repository could not be initialized. Check the name and try again.'),
                'values' => $values,
            ]);
            return;
        }
        flash('success', t('Repository initialized with its first commit.'));
        redirect(repo_url($repository));
    }

    public function show(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo);
        $git = $this->git($repository);
        $branch = (string) ($_GET['ref'] ?? $repository['default_branch']);
        $path = trim((string) ($_GET['path'] ?? ''), '/');
        try {
            $branches = $git->branches();
            if (!in_array($branch, $branches, true)) {
                abort(404);
            }
            $entries = $git->tree($branch, $path);
            $latest = $git->commits($branch, 1)[0] ?? null;
            $readme = null;
            foreach ($entries as $entry) {
                if ($entry['type'] === 'blob' && strtolower($entry['name']) === 'readme.md') {
                    $file = $git->file($branch, $entry['path']);
                    $readme = $file['content'] ?? null;
                    break;
                }
            }
        } catch (InvalidArgumentException) {
            abort(404);
        }
        $this->attachCounts($repository);
        View::render('repositories/show', [
            'title' => $repository['username'] . '/' . $repository['name'],
            'repository' => $repository,
            'activeTab' => 'code',
            'git' => $git,
            'branches' => $branches,
            'branch' => $branch,
            'path' => $path,
            'entries' => $entries,
            'latest' => $latest,
            'readme' => $readme,
        ]);
    }

    public function blob(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo);
        $branch = (string) ($_GET['ref'] ?? $repository['default_branch']);
        $path = (string) ($_GET['path'] ?? '');
        try {
            $file = $this->git($repository)->file($branch, $path);
        } catch (InvalidArgumentException) {
            abort(404);
        } catch (Throwable $exception) {
            error_log((string) $exception);
            flash('error', t('This file is too large to display. Use Git to retrieve it locally.'));
            redirect(repo_url($repository));
        }
        if (!$file) {
            abort(404);
        }
        $this->attachCounts($repository);
        View::render('repositories/blob', [
            'title' => basename($path) . ' · ' . $repository['name'],
            'repository' => $repository,
            'activeTab' => 'code',
            'branch' => $branch,
            'path' => $path,
            'file' => $file,
            'binary' => str_contains($file['content'], "\0"),
        ]);
    }

    public function raw(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo);
        $file = $this->git($repository)->file(
            (string) ($_GET['ref'] ?? $repository['default_branch']),
            (string) ($_GET['path'] ?? '')
        );
        if (!$file) {
            abort(404);
        }
        $binary = str_contains($file['content'], "\0");
        header('Content-Type: ' . ($binary ? 'application/octet-stream' : 'text/plain; charset=utf-8'));
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . strlen($file['content']));
        header("Content-Disposition: inline; filename=\"download.txt\"; filename*=UTF-8''" . rawurlencode(basename($file['path'])));
        echo $file['content'];
    }

    public function commits(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo);
        $git = $this->git($repository);
        $branch = (string) ($_GET['ref'] ?? $repository['default_branch']);
        $commits = $git->commits($branch, 50);
        if (!$commits && !$git->resolveBranch($branch)) {
            abort(404);
        }
        $this->attachCounts($repository);
        View::render('repositories/commits', [
            'title' => t('Commit history') . ' · ' . $repository['name'],
            'repository' => $repository,
            'activeTab' => 'code',
            'branch' => $branch,
            'branches' => $git->branches(),
            'commits' => $commits,
        ]);
    }

    public function commit(string $owner, string $repo, string $sha): void
    {
        $repository = $this->repository($owner, $repo);
        if (!preg_match('/^[0-9a-fA-F]{40}$/', $sha)) {
            abort(404);
        }
        $git = $this->git($repository);
        try {
            $commit = $git->commit(strtolower($sha));
            $result = $git->run(['show', '--format=', '--no-color', '--root', strtolower($sha), '--']);
            if (!$result['success']) {
                abort(404);
            }
            $diff = $result['stdout'];
        } catch (Throwable) {
            abort(404);
        }
        $this->attachCounts($repository);
        View::render('repositories/commit', [
            'title' => short_sha($sha) . ' · ' . $repository['name'],
            'repository' => $repository,
            'activeTab' => 'code',
            'commit' => $commit,
            'diff' => $diff,
        ]);
    }

    public function settings(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo, true);
        $this->attachCounts($repository);
        View::render('repositories/settings', [
            'title' => t('Repository settings') . ' · ' . $repository['name'],
            'repository' => $repository,
            'activeTab' => 'settings',
            'branches' => $this->git($repository)->branches(),
        ]);
    }

    public function update(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo, true);
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $visibility = ($_POST['visibility'] ?? '') === 'private' ? 'private' : 'public';
        $defaultBranch = (string) ($_POST['default_branch'] ?? $repository['default_branch']);
        $git = $this->git($repository);
        if (!in_array($defaultBranch, $git->branches(), true)) {
            flash('error', t('The selected default branch does not exist.'));
            redirect(repo_url($repository, 'settings'));
        }

        try {
            $updated = RepositoryService::update((int) $repository['id'], $name, $description, $visibility);
            $result = $git->run(['symbolic-ref', 'HEAD', 'refs/heads/' . $defaultBranch]);
            if (!$result['success']) {
                throw new \RuntimeException('Unable to update default branch.');
            }
            Database::execute('UPDATE repositories SET default_branch = ? WHERE id = ?', [$defaultBranch, $repository['id']]);
            $updated['default_branch'] = $defaultBranch;
        } catch (Throwable $exception) {
            error_log((string) $exception);
            flash('error', t('Unable to save repository settings. The name may already be in use.'));
            redirect(repo_url($repository, 'settings'));
        }
        flash('success', t('Repository configuration updated.'));
        redirect(repo_url($updated, 'settings'));
    }

    public function delete(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo, true);
        try {
            RepositoryService::delete((int) $repository['id']);
        } catch (Throwable $exception) {
            error_log((string) $exception);
            flash('error', t('Repository deletion failed. Please check storage permissions.'));
            redirect(repo_url($repository, 'settings'));
        }
        flash('success', t('Repository and all associated records deleted.'));
        redirect('/dashboard');
    }

    private function repository(string $owner, string $repo, bool $write = false): array
    {
        $repository = RepositoryService::find($owner, $repo);
        if (!$repository) {
            abort(404);
        }
        if ($write) {
            Auth::requireUser();
            if (!Auth::canWrite($repository)) {
                abort(403);
            }
        } elseif (!Auth::canRead($repository)) {
            abort(Auth::check() ? 403 : 404);
        }
        return $repository;
    }

    private function git(array $repository): GitService
    {
        return new GitService(RepositoryService::path((int) $repository['id']));
    }

    private function attachCounts(array &$repository): void
    {
        $repository['issue_count'] = (int) Database::fetch(
            'SELECT COUNT(*) AS count FROM issues WHERE repository_id = ? AND status = \'open\'',
            [$repository['id']]
        )['count'];
        $repository['pull_count'] = (int) Database::fetch(
            'SELECT COUNT(*) AS count FROM pull_requests WHERE repository_id = ? AND status = \'open\'',
            [$repository['id']]
        )['count'];
    }
}
