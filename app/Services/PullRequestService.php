<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use DomainException;
use RuntimeException;
use Throwable;

final class PullRequestService
{
    public function branches(int $repositoryId): array
    {
        $result = $this->run([
            '--git-dir=' . RepositoryService::path($repositoryId),
            'for-each-ref', '--format=%(refname:short)', 'refs/heads',
        ]);
        if (!$result['success']) {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode("\n", $result['stdout']))));
    }

    public function resolveBranch(int $repositoryId, string $branch): ?string
    {
        if (!$this->validBranch($repositoryId, $branch)) {
            return null;
        }
        $result = $this->run([
            '--git-dir=' . RepositoryService::path($repositoryId),
            'rev-parse', '--verify', 'refs/heads/' . $branch . '^{commit}',
        ]);
        $sha = trim($result['stdout']);
        return $result['success'] && preg_match('/^[0-9a-f]{40}$/', $sha) ? $sha : null;
    }

    public function diff(int $repositoryId, string $baseSha, string $headSha): array
    {
        $this->assertSha($baseSha);
        $this->assertSha($headSha);
        $result = $this->run([
            '--git-dir=' . RepositoryService::path($repositoryId),
            'diff', '--no-color', '--find-renames', $baseSha . '...' . $headSha, '--',
        ], null, 3145728);
        if (!$result['success']) {
            throw new RuntimeException('Unable to compare these branches.');
        }
        return ['content' => $result['stdout'], 'truncated' => $result['truncated']];
    }

    public function commits(int $repositoryId, string $baseSha, string $headSha): array
    {
        $this->assertSha($baseSha);
        $this->assertSha($headSha);
        $result = $this->run([
            '--git-dir=' . RepositoryService::path($repositoryId),
            'log', '--max-count=100', '--format=%H%x1f%an%x1f%aI%x1f%s%x1e',
            $baseSha . '..' . $headSha, '--',
        ]);
        if (!$result['success']) {
            return [];
        }
        $commits = [];
        foreach (array_filter(explode("\x1e", $result['stdout'])) as $record) {
            $parts = explode("\x1f", trim($record));
            if (count($parts) === 4) {
                $commits[] = ['sha' => $parts[0], 'author' => $parts[1], 'date' => $parts[2], 'subject' => $parts[3]];
            }
        }
        return $commits;
    }

    public function hasChanges(int $repositoryId, string $baseSha, string $headSha): bool
    {
        $this->assertSha($baseSha);
        $this->assertSha($headSha);
        if ($baseSha === $headSha) {
            return false;
        }
        $result = $this->run([
            '--git-dir=' . RepositoryService::path($repositoryId),
            'merge-base', '--is-ancestor', $headSha, $baseSha,
        ]);
        return $result['exit_code'] === 1;
    }

    public function merge(array $repository, array $pullRequest, string $expectedBase, string $expectedHead): string
    {
        if (!Auth::canWrite($repository)) {
            throw new DomainException('You do not have permission to merge this pull request.');
        }
        $this->assertSha($expectedBase);
        $this->assertSha($expectedHead);
        $lockPath = dirname(__DIR__, 2) . '/storage/locks/repository-' . $repository['id'] . '.lock';
        $lock = fopen($lockPath, 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            throw new RuntimeException('Unable to lock repository for merging.');
        }

        $worktree = null;
        $transactionStarted = false;
        try {
            Database::pdo()->beginTransaction();
            $transactionStarted = true;
            $current = Database::fetch('SELECT * FROM pull_requests WHERE id = ? FOR UPDATE', [$pullRequest['id']]);
            if (!$current || $current['status'] !== 'open') {
                throw new DomainException('This pull request is no longer open.');
            }
            $baseSha = $this->resolveBranch((int) $repository['id'], $current['base_branch']);
            $headSha = $this->resolveBranch((int) $repository['id'], $current['head_branch']);
            if ($baseSha !== $expectedBase || $headSha !== $expectedHead) {
                throw new DomainException('A branch changed after this page was loaded. Review the new changes and try again.');
            }
            if (!$this->hasChanges((int) $repository['id'], $baseSha, $headSha)) {
                throw new DomainException('There are no new commits to merge.');
            }

            $tmpDirectory = dirname(__DIR__, 2) . '/storage/tmp';
            $worktree = $tmpDirectory . '/merge-' . $pullRequest['id'] . '-' . bin2hex(random_bytes(5));
            $path = RepositoryService::path((int) $repository['id']);
            $added = $this->run(['--git-dir=' . $path, 'worktree', 'add', '--detach', $worktree, $baseSha]);
            if (!$added['success']) {
                throw new RuntimeException('Unable to prepare an isolated merge worktree.');
            }

            $message = 'Merge pull request #' . $pullRequest['number'] . ' from ' . $pullRequest['head_branch'];
            $merged = $this->run([
                '-C', $worktree,
                '-c', 'user.name=wikicode',
                '-c', 'user.email=wikicode@localhost',
                'merge', '--no-ff', '--no-edit', $headSha, '-m', $message,
            ]);
            if (!$merged['success']) {
                $this->run(['-C', $worktree, 'merge', '--abort']);
                throw new DomainException('These branches have conflicts. Resolve them locally and push the resolution before merging.');
            }
            $revision = $this->run(['-C', $worktree, 'rev-parse', 'HEAD']);
            $mergeCommit = trim($revision['stdout']);
            $this->assertSha($mergeCommit);

            $updated = $this->run([
                '--git-dir=' . $path, 'update-ref', 'refs/heads/' . $current['base_branch'], $mergeCommit, $baseSha,
            ]);
            if (!$updated['success']) {
                throw new DomainException('The target branch changed during merging. No pull request state was changed.');
            }

            Database::execute(
                'UPDATE pull_requests SET status = \'merged\', merge_commit = ?, merged_by = ?, merged_at = UTC_TIMESTAMP(), base_sha = ?, head_sha = ? WHERE id = ? AND status = \'open\'',
                [$mergeCommit, Auth::id(), $baseSha, $headSha, $pullRequest['id']]
            );
            Database::execute('UPDATE repositories SET updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$repository['id']]);
            Database::pdo()->commit();
            $transactionStarted = false;
            return $mergeCommit;
        } finally {
            if ($transactionStarted && Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            if ($worktree !== null && is_dir($worktree)) {
                $this->run(['-C', $worktree, 'merge', '--abort']);
                $this->run(['--git-dir=' . RepositoryService::path((int) $repository['id']), 'worktree', 'remove', '--force', $worktree]);
            }
            $this->run(['--git-dir=' . RepositoryService::path((int) $repository['id']), 'worktree', 'prune']);
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function validBranch(int $repositoryId, string $branch): bool
    {
        if ($branch === '' || strlen($branch) > 150 || str_starts_with($branch, '-') || str_contains($branch, '..')) {
            return false;
        }
        $result = $this->run(['--git-dir=' . RepositoryService::path($repositoryId), 'check-ref-format', '--branch', $branch]);
        return $result['success'];
    }

    private function assertSha(string $sha): void
    {
        if (!preg_match('/^[0-9a-f]{40}$/', $sha)) {
            throw new RuntimeException('Invalid commit identifier.');
        }
    }

    private function run(array $arguments, ?string $cwd = null, int $maxOutput = 1048576): array
    {
        $directory = dirname(__DIR__, 2) . '/storage/tmp';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $stdoutPath = tempnam($directory, 'pr-out-');
        $stderrPath = tempnam($directory, 'pr-err-');
        if ($stdoutPath === false || $stderrPath === false) {
            throw new RuntimeException('Unable to allocate Git output files.');
        }
        $process = null;
        try {
            $process = proc_open(
                array_merge(['git'], $arguments),
                [0 => ['file', '/dev/null', 'r'], 1 => ['file', $stdoutPath, 'w'], 2 => ['file', $stderrPath, 'w']],
                $pipes,
                $cwd
            );
            if (!is_resource($process)) {
                throw new RuntimeException('Unable to execute Git.');
            }
            $exitCode = proc_close($process);
            $process = null;
            $stdout = file_get_contents($stdoutPath, false, null, 0, $maxOutput + 1);
            $stderr = file_get_contents($stderrPath, false, null, 0, 65536);
            $truncated = strlen($stdout) > $maxOutput;
            if ($truncated) {
                $stdout = substr($stdout, 0, $maxOutput);
            }
            return [
                'success' => $exitCode === 0,
                'exit_code' => $exitCode,
                'stdout' => $stdout,
                'stderr' => $stderr,
                'truncated' => $truncated,
            ];
        } finally {
            if (is_resource($process)) {
                proc_terminate($process);
                proc_close($process);
            }
            if (is_file($stdoutPath)) {
                unlink($stdoutPath);
            }
            if (is_file($stderrPath)) {
                unlink($stderrPath);
            }
        }
    }
}
