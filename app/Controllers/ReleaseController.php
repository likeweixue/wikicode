<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\GitService;
use App\Services\ReleaseService;
use App\Services\RepositoryService;
use Throwable;

final class ReleaseController
{
    public function index(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo);
        $releases = ReleaseService::summaries((int) $repository['id']);
        $tags = $this->git($repository)->tags();
        $published = [];
        foreach ($releases as $release) {
            $published[$release['tag']] = true;
        }
        $unreleasedTags = array_values(array_filter($tags, static fn (string $tag): bool => !isset($published[$tag])));

        View::render('releases/index', [
            'title' => t('Releases') . ' · ' . $repository['name'],
            'repository' => $repository,
            'activeTab' => 'releases',
            'releases' => $releases,
            'unreleasedTags' => $unreleasedTags,
            'tags' => $tags,
        ]);
    }

    public function create(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo, true);
        $git = $this->git($repository);
        $this->renderForm($repository, $git, [
            'tag' => trim((string) ($_GET['tag'] ?? '')),
            'branch' => (string) $repository['default_branch'],
            'title' => '',
            'body' => '',
            'prerelease' => false,
        ], null);
    }

    public function store(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo, true);
        $git = $this->git($repository);
        $values = [
            'tag' => trim((string) ($_POST['tag'] ?? '')),
            'branch' => (string) ($_POST['branch'] ?? $repository['default_branch']),
            'title' => trim((string) ($_POST['title'] ?? '')),
            'body' => (string) ($_POST['body'] ?? ''),
            'prerelease' => !empty($_POST['prerelease']),
        ];

        $error = null;
        if ($values['tag'] === '') {
            $error = t('Please provide a tag name.');
        } elseif (ReleaseService::findByTag((int) $repository['id'], $values['tag']) !== null) {
            $error = t('A release already exists for this tag.');
        } else {
            try {
                if (!in_array($values['tag'], $git->tags(), true)) {
                    $git->createTag($values['tag'], $values['branch']);
                }
                $releaseId = ReleaseService::create(
                    (int) $repository['id'],
                    $values['tag'],
                    $values['title'] !== '' ? $values['title'] : $values['tag'],
                    $values['body'],
                    Auth::id(),
                    $values['prerelease']
                );
                if (isset($_FILES['assets'])) {
                    ReleaseService::storeUploadedAssets((int) $repository['id'], $releaseId, $_FILES['assets']);
                }
                flash('success', t('Release published.'));
                redirect(repo_url($repository, 'releases/' . $releaseId));
            } catch (Throwable $exception) {
                error_log('release store failed: ' . $exception->getMessage());
                $error = t('Unable to publish the release. Check the tag name and try again.');
            }
        }

        $this->renderForm($repository, $git, $values, $error);
    }

    public function show(string $owner, string $repo, string $release): void
    {
        $repository = $this->repository($owner, $repo);
        $record = ReleaseService::find((int) $repository['id'], (int) $release);
        if (!$record) {
            abort(404);
        }

        View::render('releases/show', [
            'title' => $record['title'] . ' · ' . $repository['name'],
            'repository' => $repository,
            'activeTab' => 'releases',
            'release' => $record,
            'assets' => ReleaseService::assets((int) $record['id']),
        ]);
    }

    public function uploadAsset(string $owner, string $repo, string $release): void
    {
        $repository = $this->repository($owner, $repo, true);
        $record = ReleaseService::find((int) $repository['id'], (int) $release);
        if (!$record) {
            abort(404);
        }
        try {
            ReleaseService::storeUploadedAssets((int) $repository['id'], (int) $record['id'], $_FILES['assets'] ?? []);
            flash('success', t('Attachments uploaded.'));
        } catch (Throwable $exception) {
            error_log('release asset upload failed: ' . $exception->getMessage());
            flash('error', t('Unable to upload the attachment.'));
        }
        redirect(repo_url($repository, 'releases/' . $record['id']));
    }

    public function download(string $owner, string $repo, string $release, string $asset): void
    {
        $repository = $this->repository($owner, $repo);
        $record = ReleaseService::find((int) $repository['id'], (int) $release);
        if (!$record) {
            abort(404);
        }
        $file = ReleaseService::findAsset((int) $record['id'], (int) $asset);
        if (!$file) {
            abort(404);
        }
        $path = ReleaseService::assetDir((int) $repository['id'], (int) $record['id']) . '/' . $file['stored_name'];
        if (!is_file($path)) {
            abort(404);
        }

        ReleaseService::incrementAssetDownload((int) $file['id']);
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . (string) filesize($path));
        header("Content-Disposition: attachment; filename=\"download\"; filename*=UTF-8''" . rawurlencode((string) $file['filename']));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function tags(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo);
        $git = $this->git($repository);
        $tags = [];
        foreach ($git->tags() as $tag) {
            $tags[] = ['name' => $tag, 'release' => ReleaseService::findByTag((int) $repository['id'], $tag)];
        }

        View::render('repositories/tags', [
            'title' => t('Tags') . ' · ' . $repository['name'],
            'repository' => $repository,
            'activeTab' => 'releases',
            'tags' => $tags,
            'branches' => $git->branches(),
            'defaultBranch' => (string) $repository['default_branch'],
            'error' => null,
        ]);
    }

    public function storeTag(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo, true);
        $git = $this->git($repository);
        $name = trim((string) ($_POST['tag'] ?? ''));
        $branch = (string) ($_POST['branch'] ?? $repository['default_branch']);
        try {
            $git->createTag($name, $branch);
            flash('success', t('Tag created.'));
        } catch (Throwable $exception) {
            error_log('create tag failed: ' . $exception->getMessage());
            flash('error', t('Unable to create the tag. Check the name and try again.'));
        }
        redirect(repo_url($repository, 'tags'));
    }

    private function renderForm(array $repository, GitService $git, array $values, ?string $error): void
    {
        View::render('releases/new', [
            'title' => t('New release') . ' · ' . $repository['name'],
            'repository' => $repository,
            'activeTab' => 'releases',
            'tags' => $git->tags(),
            'branches' => $git->branches(),
            'values' => $values,
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
}
