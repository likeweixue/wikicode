<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Services\RepositoryService;

final class WikiController
{
    public function index(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo);
        $pages = Database::fetchAll(
            'SELECT * FROM wiki_pages WHERE repository_id = ? ORDER BY parent_slug ASC, position ASC, slug ASC',
            [$repository['id']]
        );

        $page = $pages !== [] ? $pages[0] : null;

        $this->attachSidebarData($repository);
        View::render('wiki/show', [
            'title' => t('Wiki') . ' · ' . $repository['name'],
            'repository' => $repository,
            'activeTab' => 'wiki',
            'pages' => $pages,
            'page' => $page,
            'sidebar' => $repository['_sidebar'] ?? [],
        ]);
    }

    public function show(string $owner, string $repo, string $slug): void
    {
        $repository = $this->repository($owner, $repo);
        $pages = Database::fetchAll(
            'SELECT * FROM wiki_pages WHERE repository_id = ? ORDER BY parent_slug ASC, position ASC, slug ASC',
            [$repository['id']]
        );
        $page = Database::fetch(
            'SELECT * FROM wiki_pages WHERE repository_id = ? AND slug = ? LIMIT 1',
            [$repository['id'], $slug]
        );
        if (!$page) {
            abort(404);
        }
        $this->attachSidebarData($repository);
        View::render('wiki/show', [
            'title' => $page['title'] . ' · Wiki',
            'repository' => $repository,
            'activeTab' => 'wiki',
            'pages' => $pages,
            'page' => $page,
            'sidebar' => $repository['_sidebar'] ?? [],
        ]);
    }

    public function create(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo, true);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $body = (string) ($_POST['body'] ?? '');
            $parentSlug = trim((string) ($_POST['parent_slug'] ?? ''));
            $slug = self::slugify($title);

            if ($title === '' || $slug === '') {
                flash('error', t('Please provide a title.'));
                redirect(repo_url($repository, 'wiki/_new'));
            }
            if (strlen($body) > 200_000) {
                flash('error', t('Content is too long.'));
                redirect(repo_url($repository, 'wiki/_new'));
            }
            $exists = Database::fetch(
                'SELECT id FROM wiki_pages WHERE repository_id = ? AND slug = ?',
                [$repository['id'], $slug]
            );
            if ($exists) {
                $slug .= '-' . substr(sha1($title . microtime()), 0, 6);
            }

            Database::execute(
                'INSERT INTO wiki_pages (repository_id, slug, title, parent_slug, body, position) VALUES (?, ?, ?, ?, ?, 0)',
                [$repository['id'], $slug, $title, $parentSlug, $body]
            );
            flash('success', t('Wiki page created.'));
            redirect(repo_url($repository, 'wiki/' . $slug));
        }
        $pages = Database::fetchAll(
            'SELECT slug, title FROM wiki_pages WHERE repository_id = ? ORDER BY parent_slug ASC, position ASC, slug ASC',
            [$repository['id']]
        );
        $this->attachSidebarData($repository);
        View::render('wiki/edit', [
            'title' => t('New wiki page'),
            'repository' => $repository,
            'activeTab' => 'wiki',
            'action' => 'create',
            'page' => null,
            'parentSlugs' => $pages,
            'sidebar' => $repository['_sidebar'] ?? [],
        ]);
    }

    public function edit(string $owner, string $repo, string $slug): void
    {
        $repository = $this->repository($owner, $repo, true);
        $page = Database::fetch(
            'SELECT * FROM wiki_pages WHERE repository_id = ? AND slug = ? LIMIT 1',
            [$repository['id'], $slug]
        );
        if (!$page) {
            abort(404);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $body = (string) ($_POST['body'] ?? '');
            $parentSlug = trim((string) ($_POST['parent_slug'] ?? ''));

            if ($title === '') {
                flash('error', t('Please provide a title.'));
                redirect(repo_url($repository, 'wiki/' . $slug . '/_edit'));
            }
            Database::execute(
                'UPDATE wiki_pages SET title = ?, body = ?, parent_slug = ? WHERE id = ?',
                [$title, $body, $parentSlug, $page['id']]
            );
            flash('success', t('Wiki page updated.'));
            redirect(repo_url($repository, 'wiki/' . $page['slug']));
        }
        $pages = Database::fetchAll(
            'SELECT slug, title FROM wiki_pages WHERE repository_id = ? ORDER BY parent_slug ASC, position ASC, slug ASC',
            [$repository['id']]
        );
        $this->attachSidebarData($repository);
        View::render('wiki/edit', [
            'title' => t('Edit') . ' ' . $page['title'],
            'repository' => $repository,
            'activeTab' => 'wiki',
            'action' => 'edit',
            'page' => $page,
            'parentSlugs' => $pages,
            'sidebar' => $repository['_sidebar'] ?? [],
        ]);
    }

    public function delete(string $owner, string $repo, string $slug): void
    {
        $repository = $this->repository($owner, $repo, true);
        Database::execute(
            'DELETE FROM wiki_pages WHERE repository_id = ? AND slug = ?',
            [$repository['id'], $slug]
        );
        flash('success', t('Wiki page deleted.'));
        redirect(repo_url($repository, 'wiki'));
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

    private function attachSidebarData(array &$repository): void
    {
        $repository['_sidebar'] = \App\Controllers\RepositoryController::sidebarData($repository);
    }

    private static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text) ?? '';
        $text = preg_replace('/[\s-]+/', '-', $text) ?? '';
        return trim($text, '-');
    }
}
