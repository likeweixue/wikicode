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

    /**
     * 返回提交总数（仅默认分支可达历史）。
     */
    public function countCommits(string $branch): int
    {
        $result = $this->run(['rev-list', '--count', $branch]);
        if (!$result['success']) {
            return 0;
        }
        return max(0, (int) trim($result['stdout']));
    }

    /**
     * 标签列表，按创建时间倒序。
     *
     * @return string[]
     */
    public function tags(): array
    {
        $result = $this->run(['tag', '--list', '--sort=-creatordate']);
        if (!$result['success']) {
            return [];
        }
        $lines = preg_split('/\r?\n/', trim($result['stdout'])) ?: [];
        return array_values(array_filter($lines, static fn (string $l): bool => trim($l) !== ''));
    }

    /**
     * 按路径关键字搜索仓库中的文件。
     *
     * @return string[]
     */
    public function searchFiles(string $branch, string $query, int $limit = 50): array
    {
        $needle = mb_strtolower(trim($query), 'UTF-8');
        if ($needle === '') {
            return [];
        }
        $result = $this->run(['ls-tree', '-r', '--name-only', $branch]);
        if (!$result['success']) {
            return [];
        }
        $matches = [];
        foreach (preg_split('/\r?\n/', trim($result['stdout'])) ?: [] as $path) {
            if ($path === '') {
                continue;
            }
            if (mb_strpos(mb_strtolower($path, 'UTF-8'), $needle) !== false) {
                $matches[] = $path;
                if (count($matches) >= $limit) {
                    break;
                }
            }
        }
        return $matches;
    }

    /**
     * 将某个 ref 打包为 zip 文件，返回是否成功。
     */
    public function archive(string $ref, string $outputFile): bool
    {
        $result = $this->run(['archive', '--format=zip', '--output=' . $outputFile, $ref]);
        return $result['success'] && is_file($outputFile);
    }

    /**
     * 在指定分支上创建（或覆盖）一个文件并提交。
     * 通过临时 worktree 完成，提交后分支自动前进。
     */
    public function createFile(string $branch, string $path, string $content, string $message, string $authorName, string $authorEmail): void
    {
        $path = trim($path, '/');
        if ($path === '' || str_contains($path, '..') || str_contains($path, "\0") || str_starts_with($path, '/')) {
            throw new InvalidArgumentException('Invalid file path.');
        }
        if ($message === '') {
            $message = 'Create ' . $path;
        }

        $tmpBase = dirname(__DIR__, 2) . '/storage/tmp';
        if (!is_dir($tmpBase)) {
            @mkdir($tmpBase, 0775, true);
        }
        $tmp = $tmpBase . '/wt_' . uniqid('', true);

        $this->requireSuccess($this->run(['worktree', 'add', '--force', $tmp, $branch]), 'Unable to create worktree');
        try {
            $full = $tmp . '/' . $path;
            $dir = dirname($full);
            if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new RuntimeException('Unable to create directory for file.');
            }
            if (file_put_contents($full, $content) === false) {
                throw new RuntimeException('Unable to write file content.');
            }
            $this->requireSuccess($this->run(['add', '--', $path], $tmp), 'git add failed');
            $this->requireSuccess(
                $this->run(['-c', 'user.name=' . $authorName, '-c', 'user.email=' . $authorEmail, 'commit', '-m', $message], $tmp),
                'git commit failed'
            );
        } finally {
            $this->run(['worktree', 'remove', '--force', $tmp]);
        }
    }

    /**
     * 贡献者统计：返回 [author_email => ['name' => author_name, 'commits' => int]]。
     *
     * @return array<string, array{name: string, commits: int}>
     */
    public function contributors(string $branch, int $limit = 30): array
    {
        $result = $this->run([
            'log',
            '--no-color',
            '--format=%ae%x1f%an',
            $branch,
            '--',
        ], null, '', 30, 8 * 1024 * 1024);

        if (!$result['success']) {
            return [];
        }

        $contributors = [];
        foreach (preg_split('/\r?\n/', rtrim($result['stdout'], "\r\n")) ?: [] as $line) {
            if ($line === '') {
                continue;
            }
            $parts = explode("\x1f", $line, 2);
            if (count($parts) !== 2) {
                continue;
            }
            [$email, $name] = $parts;
            $email = strtolower(trim($email));
            if (!isset($contributors[$email])) {
                $contributors[$email] = ['name' => trim($name), 'commits' => 0];
            }
            $contributors[$email]['commits']++;
        }

        usort($contributors, static fn (array $a, array $b): int => $b['commits'] <=> $a['commits']);
        return array_slice($contributors, 0, $limit, true);
    }

    /**
     * 按文件扩展名粗略估算语言占比。
     *
     * @return array<string, float>  语言名 => 百分比，已降序
     */
    public function languageStats(string $branch, int $top = 6): array
    {
        $result = $this->run([
            'ls-tree',
            '-r',
            '-z',
            '--name-only',
            $branch,
            '--',
        ], null, '', 30, 16 * 1024 * 1024);

        if (!$result['success']) {
            return [];
        }

        $counts = [];
        $total = 0;
        foreach (explode("\0", $result['stdout']) as $path) {
            if ($path === '' || str_starts_with($path, '.')) {
                continue;
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $lang = self::EXT_MAP[$ext] ?? null;
            if ($lang === null) {
                continue;
            }
            $counts[$lang] = ($counts[$lang] ?? 0) + 1;
            $total++;
        }

        if ($total === 0) {
            return [];
        }

        $stats = [];
        foreach ($counts as $lang => $count) {
            $stats[$lang] = round(($count / $total) * 100, 1);
        }
        arsort($stats);
        return array_slice($stats, 0, $top, true);
    }

    /**
     * 按周统计提交数，返回最近 52 周 [week_key => count]。
     *
     * @return array<string, int>
     */
    public function commitsByWeek(string $branch, int $weeks = 52): array
    {
        $result = $this->run([
            'log',
            '--no-color',
            '--format=%ad',
            '--date=format:%Y-%W',
            $branch,
            '--',
        ], null, '', 30, 8 * 1024 * 1024);

        if (!$result['success']) {
            return [];
        }

        $counts = [];
        foreach (preg_split('/\r?\n/', rtrim($result['stdout'], "\r\n")) ?: [] as $week) {
            if ($week !== '') {
                $counts[$week] = ($counts[$week] ?? 0) + 1;
            }
        }
        return $counts;
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

    /** @var array<string, string>  扩展名到语言名的映射 */
    private const EXT_MAP = [
        'php' => 'PHP',
        'html' => 'HTML',
        'htm' => 'HTML',
        'css' => 'CSS',
        'scss' => 'SCSS',
        'js' => 'JavaScript',
        'jsx' => 'JavaScript',
        'ts' => 'TypeScript',
        'tsx' => 'TypeScript',
        'json' => 'JSON',
        'md' => 'Markdown',
        'py' => 'Python',
        'rb' => 'Ruby',
        'go' => 'Go',
        'rs' => 'Rust',
        'java' => 'Java',
        'kt' => 'Kotlin',
        'swift' => 'Swift',
        'c' => 'C',
        'h' => 'C',
        'cpp' => 'C++',
        'hpp' => 'C++',
        'cs' => 'C#',
        'sh' => 'Shell',
        'bash' => 'Shell',
        'zsh' => 'Shell',
        'yml' => 'YAML',
        'yaml' => 'YAML',
        'xml' => 'XML',
        'sql' => 'SQL',
        'vue' => 'Vue',
        'svelte' => 'Svelte',
        'dart' => 'Dart',
        'ex' => 'Elixir',
        'erl' => 'Erlang',
        'hs' => 'Haskell',
        'scala' => 'Scala',
        'r' => 'R',
        'lua' => 'Lua',
        'pl' => 'Perl',
        'coco' => 'Cocoa',
        'graphql' => 'GraphQL',
        'dockerfile' => 'Dockerfile',
        'makefile' => 'Makefile',
        'cmake' => 'CMake',
        'tf' => 'Terraform',
        'env' => 'Shell',
        'ini' => 'INI',
        'conf' => 'Config',
    ];
}
