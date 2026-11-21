<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\SimpleYaml;
use InvalidArgumentException;
use RuntimeException;

/**
 * Workflow 服务：解析 .wikicode.yml、同步执行（docker run 隔离）、记录日志。
 *
 * workflow 文件放在仓库根目录的 .wikicode.yml，schema：
 *
 *   name: CI
 *   on: [push, manual]
 *   jobs:
 *     build:
 *       image: node:20
 *       steps:
 *         - run: node --version
 *         - run: npm install
 */
final class WorkflowService
{
    public const WORKFLOW_PATH = '.wikicode.yml';
    public const DEFAULT_IMAGE = 'alpine:3.20';

    /**
     * 解析 yaml 文本，校验 schema，返回规范化数组。
     */
    public static function parse(string $yaml): array
    {
        $data = SimpleYaml::parse($yaml);
        if (!is_array($data) || $data === []) {
            throw new InvalidArgumentException('Empty or invalid YAML');
        }
        $name = (string) ($data['name'] ?? 'workflow');
        $on = $data['on'] ?? ['manual'];
        if (!is_array($on)) $on = [$on];
        $jobs = $data['jobs'] ?? null;
        if (!is_array($jobs) || $jobs === []) {
            throw new InvalidArgumentException('workflow must define at least one job');
        }
        $normJobs = [];
        foreach ($jobs as $jobName => $job) {
            if (!is_array($job)) {
                throw new InvalidArgumentException("job '{$jobName}' must be a map");
            }
            $image = (string) ($job['image'] ?? self::DEFAULT_IMAGE);
            $steps = $job['steps'] ?? [];
            if (!is_array($steps) || $steps === []) {
                throw new InvalidArgumentException("job '{$jobName}' must have at least one step");
            }
            $normSteps = [];
            foreach ($steps as $step) {
                if (is_string($step)) {
                    $normSteps[] = ['run' => $step];
                } elseif (is_array($step) && isset($step['run'])) {
                    $normSteps[] = ['run' => (string) $step['run'], 'name' => (string) ($step['name'] ?? '')];
                } else {
                    throw new InvalidArgumentException("job '{$jobName}' has invalid step (only 'run' is supported)");
                }
            }
            $normJobs[$jobName] = ['image' => $image, 'steps' => $normSteps];
        }
        return ['name' => $name, 'on' => $on, 'jobs' => $normJobs];
    }

    /**
     * 从 bare 仓库读 .wikicode.yml 并解析。找不到返回 null。
     */
    public static function loadFromRepo(array $repository, string $branch): ?array
    {
        $git = new GitService(RepositoryService::path((int) $repository['id']));
        try {
            $file = $git->file($branch, self::WORKFLOW_PATH);
        } catch (\Throwable) {
            return null;
        }
        $content = $file['content'] ?? null;
        if (!$content) return null;
        return self::parse($content);
    }

    /**
     * 手动触发一次 workflow：创建 run 记录并同步执行。返回 run id。
     */
    public static function trigger(array $repository, int $userId, string $branch): int
    {
        $workflow = self::loadFromRepo($repository, $branch);
        if (!$workflow) {
            throw new InvalidArgumentException('No .wikicode.yml found in branch ' . $branch);
        }

        $git = new GitService(RepositoryService::path((int) $repository['id']));
        $sha = $git->resolveBranch($branch) ?? '';

        $runId = self::createRun([
            'repository_id' => (int) $repository['id'],
            'workflow_name' => $workflow['name'],
            'branch' => $branch,
            'commit_sha' => $sha,
            'trigger_type' => 'manual',
            'triggered_by' => $userId,
        ]);

        // 同步执行（会阻塞当前请求直到完成）
        self::execute($runId, $repository, $workflow, $branch);
        return $runId;
    }

    public static function createRun(array $fields): int
    {
        Database::execute(
            'INSERT INTO workflow_runs (repository_id, workflow_name, branch, commit_sha, trigger_type, triggered_by, status) '
            . 'VALUES (?, ?, ?, ?, ?, ?, \'queued\')',
            [
                $fields['repository_id'],
                $fields['workflow_name'],
                $fields['branch'],
                $fields['commit_sha'] ?? '',
                $fields['trigger_type'] ?? 'manual',
                $fields['triggered_by'] ?? null,
            ]
        );
        return (int) Database::fetch('SELECT LAST_INSERT_ID() AS id')['id'];
    }

    /**
     * 同步执行 run：clone 仓库到临时目录 → 逐个 job 用 docker run 跑 → 写日志和状态。
     */
    public static function execute(int $runId, array $repository, array $workflow, string $branch): void
    {
        self::updateStatus($runId, 'running');
        $log = '';
        $ok = true;
        $err = null;
        $tmpDir = sys_get_temp_dir() . '/wf-' . $runId . '-' . bin2hex(random_bytes(4));

        try {
            // clone 仓库到临时目录
            $sourcePath = RepositoryService::path((int) $repository['id']);
            $cloneCmd = sprintf(
                'git clone --quiet --branch %s %s %s 2>&1',
                escapeshellarg($branch),
                escapeshellarg($sourcePath),
                escapeshellarg($tmpDir)
            );
            exec($cloneCmd, $cloneOut, $cloneRc);
            if ($cloneRc !== 0) {
                throw new RuntimeException('git clone failed: ' . implode("\n", $cloneOut));
            }
            $log .= "[setup] cloned {$repository['username']}/{$repository['name']} @ {$branch}\n";

            // 串行执行每个 job
            foreach ($workflow['jobs'] as $jobName => $job) {
                $log .= "\n=== job: {$jobName} (image: {$job['image']}) ===\n";
                $scriptParts = [];
                foreach ($job['steps'] as $i => $step) {
                    $stepName = $step['name'] !== '' ? $step['name'] : ('step ' . ($i + 1));
                    $log .= "[step] {$stepName}: {$step['run']}\n";
                    $scriptParts[] = $step['run'];
                }
                $script = implode(' && ', $scriptParts);

                // Docker-outside-of-Docker: 用 docker create + cp + start 而非 -v
                // （因为 -v 的路径是宿主机路径，容器内 clone 的目录宿主机看不到）
                $containerName = 'wf-' . $runId . '-' . $jobName . '-' . bin2hex(random_bytes(3));
                $createCmd = sprintf(
                    'docker create --name %s --network none -w /workspace %s sh -c %s 2>&1',
                    escapeshellarg($containerName),
                    escapeshellarg($job['image']),
                    escapeshellarg($script)
                );
                exec($createCmd, $createOut, $createRc);
                $log .= implode("\n", $createOut) . "\n";
                if ($createRc !== 0) {
                    $ok = false;
                    $err = "job '{$jobName}' docker create failed";
                    break;
                }
                // 把 clone 的代码 cp 进容器
                $cpCmd = sprintf('docker cp %s/. %s:/workspace/ 2>&1', escapeshellarg($tmpDir), escapeshellarg($containerName));
                exec($cpCmd, $cpOut, $cpRc);
                if ($cpRc !== 0) {
                    $log .= implode("\n", $cpOut) . "\n";
                    $ok = false;
                    $err = "job '{$jobName}' docker cp failed";
                    exec(sprintf('docker rm -f %s 2>/dev/null', escapeshellarg($containerName)));
                    break;
                }
                // 启动并附加输出
                $startCmd = sprintf('docker start -a %s 2>&1', escapeshellarg($containerName));
                exec($startCmd, $out, $rc);
                $log .= implode("\n", $out) . "\n";
                $log .= "[job {$jobName}] exit={$rc}\n";
                unset($out);
                // 清理容器
                exec(sprintf('docker rm -f %s 2>/dev/null', escapeshellarg($containerName)));
                if ($rc !== 0) {
                    $ok = false;
                    $err = "job '{$jobName}' failed with exit code {$rc}";
                    break;
                }
            }
        } catch (\Throwable $e) {
            $ok = false;
            $err = $e->getMessage();
            $log .= "\n[error] " . $err . "\n";
        } finally {
            // 清理临时目录
            self::cleanup($tmpDir);
        }

        self::finishRun($runId, $ok ? 'success' : 'failure', $log, $err);
    }

    public static function updateStatus(int $runId, string $status): void
    {
        if ($status === 'running') {
            Database::execute('UPDATE workflow_runs SET status = ?, started_at = NOW() WHERE id = ?', [$status, $runId]);
        } else {
            Database::execute('UPDATE workflow_runs SET status = ? WHERE id = ?', [$status, $runId]);
        }
    }

    public static function finishRun(int $runId, string $status, string $log, ?string $error): void
    {
        Database::execute(
            'UPDATE workflow_runs SET status = ?, finished_at = NOW(), log = ?, error = ? WHERE id = ?',
            [$status, $log, $error, $runId]
        );
    }

    public static function getRun(int $runId): ?array
    {
        return Database::fetch(
            'SELECT wr.*, u.username AS triggered_by_username
             FROM workflow_runs wr LEFT JOIN users u ON u.id = wr.triggered_by
             WHERE wr.id = ? LIMIT 1',
            [$runId]
        ) ?: null;
    }

    public static function listRuns(int $repositoryId, int $limit = 30): array
    {
        return Database::fetchAll(
            'SELECT wr.*, u.username AS triggered_by_username
             FROM workflow_runs wr LEFT JOIN users u ON u.id = wr.triggered_by
             WHERE wr.repository_id = ?
             ORDER BY wr.id DESC LIMIT ' . (int) $limit,
            [$repositoryId]
        );
    }

    private static function cleanup(string $dir): void
    {
        if (!is_dir($dir)) return;
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $file->isDir() && !$file->isLink() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }
        @rmdir($dir);
    }
}
