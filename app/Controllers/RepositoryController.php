<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Services\GitService;
use App\Services\LicenseService;
use App\Services\ReleaseService;
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
        $homepage = trim((string) ($_POST['homepage'] ?? ''));
        $license = trim((string) ($_POST['license'] ?? ''));
        $values = compact('name', 'description', 'visibility', 'homepage', 'license');

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
        if ($homepage !== '' && !preg_match('#^https?://[^\s<>"\']+$#i', $homepage)) {
            View::render('repositories/create', [
                'title' => t('New repository'),
                'error' => t('Homepage must be a valid http:// or https:// URL.'),
                'values' => $values,
            ]);
            return;
        }

        try {
            $repository = RepositoryService::create((int) Auth::id(), $name, $description, $visibility, $homepage, $license);
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

    public static function sidebarData(array $repository): array
    {
        $starCount = RepositoryService::starCount((int) $repository['id']);
        $watchCount = RepositoryService::watchCount((int) $repository['id']);
        $forkCount = RepositoryService::forkCount((int) $repository['id']);
        $isStarred = false;
        $isWatched = false;
        if (Auth::check()) {
            $isStarred = RepositoryService::isStarred((int) $repository['id'], (int) Auth::id());
            $isWatched = RepositoryService::isWatched((int) $repository['id'], (int) Auth::id());
        }

        $git = new GitService(RepositoryService::path((int) $repository['id']));
        $languages = [];
        try {
            $languages = $git->languageStats($repository['default_branch'], 6);
        } catch (Throwable) {
        }
        $colors = RepositoryService::languageColors();

        $licenseName = '';
        $licenseUrl = '';
        if (!empty($repository['license']) && \App\Services\LicenseService::exists($repository['license'])) {
            $info = \App\Services\LicenseService::available()[$repository['license']];
            $licenseName = $info['name'];
            $licenseUrl = $info['url'];
        }

        return [
            'starCount' => $starCount,
            'watchCount' => $watchCount,
            'forkCount' => $forkCount,
            'isStarred' => $isStarred,
            'isWatched' => $isWatched,
            'languages' => $languages,
            'languageColors' => $colors,
            'licenseName' => $licenseName,
            'licenseUrl' => $licenseUrl,
            'releases' => ReleaseService::summaries((int) $repository['id'], 4),
        ];
    }

    public function toggleStar(string $owner, string $repo): void
    {
        Auth::requireUser();
        $repository = $this->repository($owner, $repo);
        RepositoryService::toggleStar((int) $repository['id'], (int) Auth::id());
        $this->back();
    }

    public function toggleWatch(string $owner, string $repo): void
    {
        Auth::requireUser();
        $repository = $this->repository($owner, $repo);
        RepositoryService::toggleWatch((int) $repository['id'], (int) Auth::id());
        $this->back();
    }

    private function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        if ($referer) {
            header('Location: ' . $referer);
            exit;
        }
        redirect('/');
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
        } catch (InvalidArgumentException | \RuntimeException) {
            abort(404);
        }
        $this->attachCounts($repository);
        $sidebar = self::sidebarData($repository);
        $search = trim((string) ($_GET['q'] ?? ''));
        $searchResults = $search !== '' ? $git->searchFiles($branch, $search, 50) : [];
        $fileCommits = $git->latestCommitByPath($branch);
        $entryCommits = [];
        foreach ($entries as $entry) {
            if ($entry['type'] === 'blob') {
                $entryCommits[$entry['path']] = $fileCommits[$entry['path']] ?? null;
                continue;
            }
            $latestForDirectory = null;
            foreach ($fileCommits as $filePath => $info) {
                if (str_starts_with($filePath, $entry['path'] . '/')
                    && ($latestForDirectory === null || strcmp((string) $info['date'], (string) $latestForDirectory['date']) > 0)) {
                    $latestForDirectory = $info;
                }
            }
            $entryCommits[$entry['path']] = $latestForDirectory;
        }
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
            'sidebar' => $sidebar,
            'tags' => $git->tags(),
            'commitCount' => $git->countCommits($branch),
            'entryCommits' => $entryCommits,
            'search' => $search,
            'searchResults' => $searchResults,
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
            'activeTab' => 'history',
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
        $homepage = trim((string) ($_POST['homepage'] ?? ''));
        $defaultBranch = (string) ($_POST['default_branch'] ?? $repository['default_branch']);
        $git = $this->git($repository);
        if (!in_array($defaultBranch, $git->branches(), true)) {
            flash('error', t('The selected default branch does not exist.'));
            redirect(repo_url($repository, 'settings'));
        }
        if ($homepage !== '' && !preg_match('#^https?://[^\s<>"\']+$#i', $homepage)) {
            flash('error', t('Homepage must be a valid http:// or https:// URL.'));
            redirect(repo_url($repository, 'settings'));
        }

        try {
            $updated = RepositoryService::update((int) $repository['id'], $name, $description, $visibility, $homepage);
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

    public function archive(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo);
        $ref = (string) ($_GET['ref'] ?? $repository['default_branch']);
        $git = $this->git($repository);
        $tmpDir = dirname(__DIR__, 2) . '/storage/tmp';
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }
        $tmp = $tmpDir . '/arch_' . uniqid('', true) . '.zip';
        if (!$git->archive($ref, $tmp)) {
            @unlink($tmp);
            abort(404);
        }
        $filename = $repository['name'] . '-' . preg_replace('/[^A-Za-z0-9._-]/', '-', $ref) . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . (string) filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    public function newFile(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo, true);
        $git = $this->git($repository);
        $branch = (string) ($_REQUEST['ref'] ?? $repository['default_branch']);
        $path = trim((string) ($_REQUEST['path'] ?? ''), '/');
        if (!in_array($branch, $git->branches(), true)) {
            abort(404);
        }
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filePath = trim((string) ($_POST['file_path'] ?? ''));
            $content = (string) ($_POST['content'] ?? '');
            $message = trim((string) ($_POST['message'] ?? ''));

            if (!empty($_FILES['upload']['tmp_name']) && is_uploaded_file($_FILES['upload']['tmp_name'])) {
                if ((int) $_FILES['upload']['size'] > 2 * 1024 * 1024) {
                    $error = t('File is too large (max 2 MiB).');
                } else {
                    $content = (string) file_get_contents($_FILES['upload']['tmp_name']);
                    if ($filePath === '') {
                        $filePath = basename((string) $_FILES['upload']['name']);
                    }
                }
            }
            if ($filePath === '') {
                $error = $error ?? t('Please provide a file name.');
            }

            $fullPath = ($path !== '' ? $path . '/' : '') . $filePath;
            if ($error === null) {
                try {
                    $user = Auth::user();
                    $git->createFile($branch, $fullPath, $content, $message, (string) $user['username'], (string) ($user['email'] ?? 'wikicode@localhost'));
                    flash('success', t('File committed.'));
                    $dir = dirname($fullPath);
                    redirect(repo_url($repository, '?' . http_build_query(['ref' => $branch, 'path' => $dir === '.' ? '' : $dir])));
                } catch (Throwable $e) {
                    error_log('createFile failed: ' . $e->getMessage());
                    $error = t('Unable to commit the file. It may already exist with identical content.');
                }
            }
        }
        $this->attachCounts($repository);
        $licenseTemplates = [];
        foreach (LicenseService::available() as $licenseId => $licenseInfo) {
            $licenseTemplates[] = [
                'id' => $licenseId,
                'name' => $licenseInfo['name'],
                'body' => LicenseService::render($licenseId, [
                    'year' => (string) date('Y'),
                    'author' => (string) (Auth::user()['username'] ?? ''),
                    'project' => (string) $repository['name'],
                    'projecturl' => app_url($repository['username'] . '/' . $repository['name']),
                ]),
            ];
        }
        View::render('repositories/new_file', [
            'title' => t('Add file'),
            'repository' => $repository,
            'activeTab' => 'code',
            'branch' => $branch,
            'path' => $path,
            'error' => $error,
            'licenseTemplates' => $licenseTemplates,
            'sidebar' => self::sidebarData($repository),
        ]);
    }

    public function edit(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo, true);
        $git = $this->git($repository);
        $branch = (string) ($_REQUEST['ref'] ?? $repository['default_branch']);
        $path = trim((string) ($_REQUEST['path'] ?? ''), '/');
        if ($path === '' || !in_array($branch, $git->branches(), true)) {
            abort(404);
        }
        try {
            $file = $git->file($branch, $path);
        } catch (Throwable) {
            abort(404);
        }
        if (!$file) {
            abort(404);
        }
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $content = (string) ($_POST['content'] ?? '');
            $message = trim((string) ($_POST['message'] ?? ''));
            try {
                $user = Auth::user();
                $git->updateFile($branch, $path, $content, $message, (string) $user['username'], (string) ($user['email'] ?? 'wikicode@localhost'));
                flash('success', t('File updated.'));
                redirect(repo_url($repository, 'blob?' . http_build_query(['ref' => $branch, 'path' => $path])));
            } catch (Throwable $exception) {
                error_log('updateFile failed: ' . $exception->getMessage());
                $error = t('Unable to commit the changes. The file may already contain this content.');
            }
        }
        $this->attachCounts($repository);
        View::render('repositories/edit_file', [
            'title' => t('Edit') . ' ' . basename($path),
            'repository' => $repository,
            'activeTab' => 'code',
            'branch' => $branch,
            'path' => $path,
            'file' => $file,
            'error' => $error,
        ]);
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
