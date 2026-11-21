<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\RepositoryService;
use App\Services\WorkflowService;

final class WorkflowController
{
    private function repository(string $owner, string $repo, bool $write = false): array
    {
        $repository = RepositoryService::find($owner, $repo);
        if (!$repository) abort(404);
        if ($write) {
            Auth::requireUser();
            if (!Auth::canWrite($repository)) abort(403);
        } elseif (!Auth::canRead($repository)) {
            abort(Auth::check() ? 403 : 404);
        }
        return $repository;
    }

    public function index(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo);
        $branch = (string) ($_GET['ref'] ?? $repository['default_branch']);

        $workflow = null;
        $parseError = null;
        try {
            $workflow = WorkflowService::loadFromRepo($repository, $branch);
        } catch (\Throwable $e) {
            $parseError = $e->getMessage();
        }

        $runs = WorkflowService::listRuns((int) $repository['id'], 30);

        View::render('repositories/actions', [
            'title' => $repository['username'] . '/' . $repository['name'] . ' · Actions',
            'repository' => $repository,
            'activeTab' => 'actions',
            'branch' => $branch,
            'workflow' => $workflow,
            'parseError' => $parseError,
            'runs' => $runs,
            'canWrite' => Auth::canWrite($repository),
        ]);
    }

    public function trigger(string $owner, string $repo): void
    {
        $repository = $this->repository($owner, $repo, true);
        $branch = (string) ($_POST['branch'] ?? $repository['default_branch']);
        try {
            $runId = WorkflowService::trigger($repository, (int) Auth::id(), $branch);
            redirect(repo_url($repository, 'actions/runs/' . $runId));
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            redirect(repo_url($repository, 'actions'));
        }
    }

    public function show(string $owner, string $repo, string $runId): void
    {
        $repository = $this->repository($owner, $repo);
        $run = WorkflowService::getRun((int) $runId);
        if (!$run || (int) $run['repository_id'] !== (int) $repository['id']) {
            abort(404);
        }
        View::render('repositories/actions_run', [
            'title' => $repository['username'] . '/' . $repository['name'] . ' · Run #' . $run['id'],
            'repository' => $repository,
            'activeTab' => 'actions',
            'run' => $run,
        ]);
    }
}
