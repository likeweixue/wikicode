<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\GitService;
use App\Services\RepositoryService;

final class InsightsController
{
    public function index(string $owner, string $repo): void
    {
        $repository = self::repository($owner, $repo);
        $git = new GitService(RepositoryService::path((int) $repository['id']));
        $branch = $repository['default_branch'];

        $commits = $git->countCommits($branch);
        $contributors = $git->contributors($branch, 20);
        $languages = $git->languageStats($branch, 6);
        $weeklyCommits = $git->commitsByWeek($branch, 52);

        $issueOpen = (int) \App\Core\Database::fetch(
            'SELECT COUNT(*) AS c FROM issues WHERE repository_id = ? AND status = \'open\'',
            [$repository['id']]
        )['c'];
        $issueClosed = (int) \App\Core\Database::fetch(
            'SELECT COUNT(*) AS c FROM issues WHERE repository_id = ? AND status != \'open\'',
            [$repository['id']]
        )['c'];
        $prMerged = (int) \App\Core\Database::fetch(
            'SELECT COUNT(*) AS c FROM pull_requests WHERE repository_id = ? AND status = \'merged\'',
            [$repository['id']]
        )['c'];
        $prOpen = (int) \App\Core\Database::fetch(
            'SELECT COUNT(*) AS c FROM pull_requests WHERE repository_id = ? AND status = \'open\'',
            [$repository['id']]
        )['c'];

        $this->attachSidebarData($repository);

        View::render('insights/index', [
            'title' => t('Insights') . ' · ' . $repository['name'],
            'repository' => $repository,
            'activeTab' => 'insights',
            'commits' => $commits,
            'contributors' => $contributors,
            'languages' => $languages,
            'weeklyCommits' => $weeklyCommits,
            'issueOpen' => $issueOpen,
            'issueClosed' => $issueClosed,
            'prMerged' => $prMerged,
            'prOpen' => $prOpen,
            'branchCount' => count($git->branches()),
            'sidebar' => $repository['_sidebar'] ?? [],
        ]);
    }

    private static function repository(string $owner, string $repo): array
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

    private function attachSidebarData(array &$repository): void
    {
        $repository['_sidebar'] = RepositoryController::sidebarData($repository);
    }
}
