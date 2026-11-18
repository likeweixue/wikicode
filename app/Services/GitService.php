<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class GitService
{
    private const MAX_OUTPUT = 16 * 1024 * 1024;
    private const MAX_FILE_SIZE = 2 * 1024 * 1024;
    private const COMMIT_FORMAT = '%H%x1f%P%x1f%an%x1f%ae%x1f%aI%x1f%cn%x1f%cI%x1f%s%x1f%B%x1e';

    private string $repositoryPath;

    public function __construct(string $repositoryPath)
    {
        if ($repositoryPath === '' || str_contains($repositoryPath, "\0")) {
            throw new InvalidArgumentException('Repository path must be a non-empty valid path.');
        }

        $this->repositoryPath = $repositoryPath;
    }

    /**
     * Run Git without invoking a shell. Arguments do not include the git executable.
     *
     * @return array{success: bool, stdout: string, stderr: string, exit_code: int}
     */
    public function run(
        array $arguments,
        ?string $cwd = null,
        string $stdin = '',
        int $timeout = 30,
        int $maxOutput = self::MAX_OUTPUT
    ): array {
        if ($arguments === [] || $timeout < 1 || $maxOutput < 1) {
            throw new InvalidArgumentException('Git arguments, timeout, and output limit must be valid.');
        }

        $command = ['git', '--no-pager'];
        foreach ($arguments as $argument) {
            if (!is_string($argument) || str_contains($argument, "\0")) {
                throw new InvalidArgumentException('Every Git argument must be a string without NUL bytes.');
            }
            $command[] = $argument;
        }

        $workingDirectory = $cwd ?? $this->repositoryPath;
        if (!is_dir($workingDirectory)) {
            throw new RuntimeException('Git working directory does not exist: ' . $workingDirectory);
        }

        $pipes = [];
        $process = proc_open(
            $command,
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $workingDirectory,
            null,
            ['bypass_shell' => true]
        );

        if (!is_resource($process)) {
            throw new RuntimeException('Unable to start Git process.');
        }

        foreach ($pipes as $pipe) {
            stream_set_blocking($pipe, false);
        }

        if ($stdin === '') {
            fclose($pipes[0]);
            unset($pipes[0]);
        }

        $stdout = '';
        $stderr = '';
        $stdinOffset = 0;
        $outputSize = 0;
        $exitCode = null;
        $running = true;
        $startedAt = microtime(true);

        try {
            while (true) {
                foreach ([1, 2] as $index) {
                    if (!isset($pipes[$index])) {
                        continue;
                    }

                    $chunk = fread($pipes[$index], 65536);
                    if ($chunk === false) {
                        throw new RuntimeException('Unable to read Git process output.');
                    }

                    if ($chunk !== '') {
                        $outputSize += strlen($chunk);
                        if ($outputSize > $maxOutput) {
                            throw new RuntimeException('Git command exceeded the output limit of ' . $maxOutput . ' bytes.');
                        }

                        if ($index === 1) {
                            $stdout .= $chunk;
                        } else {
                            $stderr .= $chunk;
                        }
                    }

                    if (feof($pipes[$index])) {
                        fclose($pipes[$index]);
                        unset($pipes[$index]);
                    }
                }

                if (isset($pipes[0])) {
                    if ($stdinOffset >= strlen($stdin)) {
                        fclose($pipes[0]);
                        unset($pipes[0]);
                    } else {
                        $written = @fwrite($pipes[0], substr($stdin, $stdinOffset, 65536));
                        if ($written === false) {
                            fclose($pipes[0]);
                            unset($pipes[0]);
                        } else {
                            $stdinOffset += $written;
                        }
                    }
                }

                $status = proc_get_status($process);
                if (!$status['running'] && $exitCode === null) {
                    $exitCode = $status['exitcode'];
                }
                $running = $status['running'];

                if (!$running && isset($pipes[0])) {
                    fclose($pipes[0]);
                    unset($pipes[0]);
                }

                if (!$running && !isset($pipes[1]) && !isset($pipes[2])) {
                    break;
                }

                if (microtime(true) - $startedAt > $timeout) {
                    throw new RuntimeException('Git command timed out after ' . $timeout . ' seconds.');
                }

                $read = [];
                $write = [];
                foreach ([1, 2] as $index) {
                    if (isset($pipes[$index])) {
                        $read[] = $pipes[$index];
                    }
                }
                if (isset($pipes[0])) {
                    $write[] = $pipes[0];
                }

                if ($read !== [] || $write !== []) {
                    $except = null;
                    @stream_select($read, $write, $except, 0, 200000);
                } else {
                    usleep(10000);
                }
            }
        } catch (Throwable $exception) {
            @proc_terminate($process, 9);
            foreach ($pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            proc_close($process);
            throw $exception;
        }

        $closeExitCode = proc_close($process);
        if ($exitCode === null || $exitCode === -1) {
            $exitCode = $closeExitCode;
        }

        return [
            'success' => $exitCode === 0,
            'stdout' => $stdout,
            'stderr' => $stderr,
            'exit_code' => $exitCode,
        ];
    }

    /** @return list<string> */
    public function branches(): array
    {
        $result = $this->run([
            'for-each-ref',
            '--format=%(refname:strip=2)',
            'refs/heads',
        ]);
        $this->requireSuccess($result, 'Unable to list branches');

        if (trim($result['stdout']) === '') {
            return [];
        }

        return preg_split('/\r?\n/', rtrim($result['stdout'], "\r\n")) ?: [];
    }

    public function defaultBranchExists(string $branch = 'main'): bool
    {
        return $this->resolveBranch($branch) !== null;
    }

    public function resolveBranch(?string $branch = null): ?string
    {
        $ref = 'HEAD';
        if ($branch !== null && $branch !== '') {
            $this->assertValidBranch($branch);
            $ref = 'refs/heads/' . $branch;
        }

        $result = $this->run(['rev-parse', '--verify', '--quiet', $ref . '^{commit}', '--']);
        if (!$result['success']) {
            if ($result['exit_code'] === 1 || trim($result['stderr']) === '') {
                return null;
            }
            $this->requireSuccess($result, 'Unable to resolve branch');
        }

        $sha = strtolower(trim($result['stdout']));
        $this->assertValidSha($sha);
        return $sha;
    }

    /** @return list<array{mode: string, type: string, sha: string, size: int|null, name: string, path: string}> */
    public function tree(string $branch, string $path = ''): array
    {
        $path = $this->assertValidPath($path, true);
        $commit = $this->resolveBranch($branch);
        if ($commit === null) {
            return [];
        }

        $treeish = $commit . ($path === '' ? '' : ':' . $path);
        $result = $this->run(['ls-tree', '-z', '-l', $treeish, '--']);
        $this->requireSuccess($result, 'Unable to read repository tree');

        $entries = [];
        foreach (explode("\0", $result['stdout']) as $line) {
            if ($line === '') {
                continue;
            }

            if (!preg_match('/^([0-7]+) (blob|tree|commit) ([0-9a-f]{40}) +([0-9]+|-)\t(.*)$/s', $line, $matches)) {
                throw new RuntimeException('Unexpected Git tree output.');
            }

            $entries[] = [
                'mode' => $matches[1],
                'type' => $matches[2],
                'sha' => $matches[3],
                'size' => $matches[4] === '-' ? null : (int) $matches[4],
                'name' => $matches[5],
                'path' => $path === '' ? $matches[5] : $path . '/' . $matches[5],
            ];
        }

        usort($entries, static function (array $left, array $right): int {
            if (($left['type'] === 'tree') !== ($right['type'] === 'tree')) {
                return $left['type'] === 'tree' ? -1 : 1;
            }
            return strcmp($left['name'], $right['name']);
        });

        return $entries;
    }

    /** @return array{path: string, size: int, content: string}|null */
    public function file(string $branch, string $path): ?array
    {
        $path = $this->assertValidPath($path, false);
        $commit = $this->resolveBranch($branch);
        if ($commit === null) {
            return null;
        }

        $object = $commit . ':' . $path;
        $type = $this->run(['cat-file', '-t', $object]);
        if (!$type['success'] || trim($type['stdout']) !== 'blob') {
            return null;
        }

        $sizeResult = $this->run(['cat-file', '-s', $object]);
        $this->requireSuccess($sizeResult, 'Unable to determine file size');
        $size = trim($sizeResult['stdout']);
        if (!preg_match('/^[0-9]+$/', $size)) {
            throw new RuntimeException('Unexpected Git blob size output.');
        }

        $size = (int) $size;
        if ($size > self::MAX_FILE_SIZE) {
            throw new RuntimeException('File exceeds the 2 MiB read limit: ' . $path);
        }

        $content = $this->run(['cat-file', 'blob', $object], null, '', 30, self::MAX_FILE_SIZE);
        $this->requireSuccess($content, 'Unable to read file');

        return ['path' => $path, 'size' => $size, 'content' => $content['stdout']];
    }

    /** @return list<array<string, mixed>> */
    public function commits(string $branch, int $limit = 50): array
    {
        $commit = $this->resolveBranch($branch);
        if ($commit === null) {
            return [];
        }

        return $this->history($commit, $limit);
    }

    /** @return array<string, mixed> */
    public function commit(string $sha): array
    {
        $sha = $this->normalizeSha($sha);
        $result = $this->run([
            'show',
            '--no-patch',
            '--no-color',
            '--format=' . self::COMMIT_FORMAT,
            $sha,
            '--',
        ]);
        $this->requireSuccess($result, 'Unable to read commit ' . $sha);

        $commits = $this->parseCommits($result['stdout']);
        if (count($commits) !== 1) {
            throw new RuntimeException('Unexpected Git commit output for ' . $sha);
        }

        return $commits[0];
    }

    public function diff(string $base, string $head): string
    {
        $base = $this->normalizeSha($base);
        $head = $this->normalizeSha($head);
        $result = $this->run([
            'diff',
            '--no-color',
            '--no-ext-diff',
            '--unified=3',
            $base,
            $head,
            '--',
        ]);
        $this->requireSuccess($result, 'Unable to create diff');

        return $result['stdout'];
    }

    /** @return list<array<string, mixed>> */
    public function commitsBetween(string $base, string $head, int $limit = 50): array
    {
        $base = $this->normalizeSha($base);
        $head = $this->normalizeSha($head);
        return $this->history($base . '..' . $head, $limit);
    }

    public function isAncestor(string $base, string $head): bool
    {
        $base = $this->normalizeSha($base);
        $head = $this->normalizeSha($head);
        $result = $this->run(['merge-base', '--is-ancestor', $base, $head]);

        if ($result['success']) {
            return true;
        }
        if ($result['exit_code'] === 1) {
            return false;
        }

        $this->requireSuccess($result, 'Unable to compare commits');
    }

    /** @return list<array<string, mixed>> */
    private function history(string $range, int $limit): array
    {
        $limit = max(1, min(50, $limit));
        $result = $this->run([
            'log',
            '--no-color',
            '--format=' . self::COMMIT_FORMAT,
            '--max-count=' . $limit,
            $range,
            '--',
        ]);
        $this->requireSuccess($result, 'Unable to read commit history');

        return $this->parseCommits($result['stdout']);
    }

    /** @return list<array<string, mixed>> */
    private function parseCommits(string $output): array
    {
        $commits = [];
        foreach (explode("\x1e", $output) as $record) {
            $record = ltrim($record, "\r\n");
            if ($record === '') {
                continue;
            }

            $fields = explode("\x1f", $record, 9);
            if (count($fields) !== 9 || !preg_match('/^[0-9a-f]{40}$/', $fields[0])) {
                throw new RuntimeException('Unexpected Git commit history output.');
            }

            $commits[] = [
                'sha' => $fields[0],
                'parents' => $fields[1] === '' ? [] : explode(' ', $fields[1]),
                'author_name' => $fields[2],
                'author_email' => $fields[3],
                'author_date' => $fields[4],
                'committer_name' => $fields[5],
                'committer_date' => $fields[6],
                'subject' => $fields[7],
                'message' => rtrim($fields[8], "\r\n"),
            ];
        }

        return $commits;
    }

    private function normalizeSha(string $sha): string
    {
        $sha = strtolower($sha);
        $this->assertValidSha($sha);
        return $sha;
    }

    private function assertValidSha(string $sha): void
    {
        if (!preg_match('/^[0-9a-f]{40}$/', $sha)) {
            throw new InvalidArgumentException('Commit SHA must contain exactly 40 hexadecimal characters.');
        }
    }

    private function assertValidBranch(string $branch): void
    {
        if (
            $branch === ''
            || strlen($branch) > 150
            || $branch[0] === '-'
            || str_starts_with($branch, '/')
            || str_ends_with($branch, '/')
            || str_ends_with($branch, '.')
            || str_contains($branch, '//')
            || str_contains($branch, '..')
            || str_contains($branch, '@{')
            || preg_match('#[\x00-\x20\x7f\x7e^:?*\[\]\\\\]#', $branch)
            || preg_match('#(^|/)\.#', $branch)
            || preg_match('#(^|/)[^/]+\.lock(?=/|$)#i', $branch)
        ) {
            throw new InvalidArgumentException('Invalid branch name: ' . $branch);
        }
    }

    private function assertValidPath(string $path, bool $allowEmpty): string
    {
        if ($path === '.' && $allowEmpty) {
            return '';
        }

        if (
            ($path === '' && !$allowEmpty)
            || strlen($path) > 4096
            || str_starts_with($path, '/')
            || str_ends_with($path, '/')
            || str_starts_with($path, '-')
            || str_contains($path, '\\')
            || str_contains($path, ':')
            || preg_match('/[\x00-\x1f\x7f]/', $path)
        ) {
            throw new InvalidArgumentException('Invalid repository path.');
        }

        if ($path !== '') {
            foreach (explode('/', $path) as $segment) {
                if ($segment === '' || $segment === '.' || $segment === '..') {
                    throw new InvalidArgumentException('Repository path contains an invalid segment.');
                }
            }
        }

        return $path;
    }

    /** @param array{success: bool, stdout: string, stderr: string, exit_code: int} $result */
    private function requireSuccess(array $result, string $message): void
    {
        if ($result['success']) {
            return;
        }

        $error = trim($result['stderr']);
        throw new RuntimeException(
            $message . ': ' . ($error !== '' ? $error : 'Git exited with code ' . $result['exit_code'] . '.')
        );
    }
}
