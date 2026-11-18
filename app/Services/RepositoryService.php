<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use FilesystemIterator;
use InvalidArgumentException;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;

final class RepositoryService
{
    /**
     * 根据扩展名粗判编程语言 => 颜色。
     *
     * @return array<string, string>
     */
    public static function languageColors(): array
    {
        return [
            'PHP' => '#8892bf', 'HTML' => '#e34c26', 'CSS' => '#563d7c',
            'SCSS' => '#c6538c', 'JavaScript' => '#f1e05a', 'TypeScript' => '#3178c6',
            'JSON' => '#292929', 'Markdown' => '#083fa1', 'Python' => '#3572A5',
            'Ruby' => '#701516', 'Go' => '#00ADD8', 'Rust' => '#dea584',
            'Java' => '#b07219', 'Kotlin' => '#A97BFF', 'Swift' => '#F05138',
            'C' => '#555555', 'C++' => '#f34b7d', 'C#' => '#178600',
            'Shell' => '#89e051', 'YAML' => '#cb171e', 'SQL' => '#e38c00',
            'Vue' => '#41b883', 'Svelte' => '#ff3e00', 'Dart' => '#00B4AB',
            'Elixir' => '#6e4a7e', 'Erlang' => '#B83998', 'Haskell' => '#5e5086',
            'Scala' => '#c22d40', 'R' => '#198CE7', 'Lua' => '#000080',
            'Perl' => '#0298c3', 'GraphQL' => '#e10098', 'Dockerfile' => '#384d54',
            'Makefile' => '#427819', 'Terraform' => '#844FBA', 'XML' => '#0060ac',
            'INI' => '#d18c8d', 'Config' => '#d18c8d',
        ];
    }

    public static function find(string $ownerUsername, string $repoName): ?array
    {
        return Database::fetch(
            'SELECT r.*, u.username FROM repositories r '
            . 'JOIN users u ON u.id = r.owner_id '
            . 'WHERE u.username = ? AND r.name = ? LIMIT 1',
            [$ownerUsername, $repoName]
        );
    }

    public static function findById(int $id): ?array
    {
        self::assertPositiveId($id);
        return Database::fetch(
            'SELECT r.*, u.username FROM repositories r '
            . 'JOIN users u ON u.id = r.owner_id WHERE r.id = ? LIMIT 1',
            [$id]
        );
    }

    public static function forUser(int $userId): array
    {
        self::assertPositiveId($userId);
        return Database::fetchAll(
            'SELECT r.*, u.username FROM repositories r '
            . 'JOIN users u ON u.id = r.owner_id WHERE r.owner_id = ? '
            . 'ORDER BY r.updated_at DESC, r.id DESC',
            [$userId]
        );
    }

    public static function visibleToCurrentUser(): array
    {
        if (Auth::isAdmin()) {
            return self::allWithOwners();
        }

        $userId = Auth::id();
        if ($userId === null) {
            return Database::fetchAll(
                'SELECT r.*, u.username FROM repositories r '
                . 'JOIN users u ON u.id = r.owner_id WHERE r.visibility = \'public\' '
                . 'ORDER BY r.updated_at DESC, r.id DESC'
            );
        }

        return Database::fetchAll(
            'SELECT r.*, u.username FROM repositories r '
            . 'JOIN users u ON u.id = r.owner_id '
            . 'WHERE r.visibility = \'public\' OR r.owner_id = ? '
            . 'ORDER BY r.updated_at DESC, r.id DESC',
            [$userId]
        );
    }

    public static function allWithOwners(): array
    {
        return Database::fetchAll(
            'SELECT r.*, u.username FROM repositories r '
            . 'JOIN users u ON u.id = r.owner_id ORDER BY r.updated_at DESC, r.id DESC'
        );
    }

    public static function create(int $ownerId, string $name, string $description, string $visibility, string $homepage = '', string $license = ''): array
    {
        self::assertPositiveId($ownerId);
        self::assertValidRepositoryName($name);
        self::assertValidDescription($description);
        self::assertValidVisibility($visibility);

        if ($homepage !== '') {
            self::assertValidHomepage($homepage);
        }
        if ($license !== '' && !LicenseService::exists($license)) {
            $license = '';
        }

        $owner = Database::fetch('SELECT id, username FROM users WHERE id = ? LIMIT 1', [$ownerId]);
        if ($owner === null) {
            throw new InvalidArgumentException('Repository owner does not exist: ' . $ownerId);
        }
        self::assertValidUsername((string) $owner['username']);

        $path = null;
        $cleanPathOnFailure = false;

        try {
            $id = Database::transaction(static function (PDO $pdo) use (
                $ownerId,
                $name,
                $description,
                $visibility,
                $homepage,
                $license,
                &$path,
                &$cleanPathOnFailure
            ): int {
                Database::execute(
                    'INSERT INTO repositories (owner_id, name, description, visibility, homepage, license, default_branch) '
                    . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [$ownerId, $name, $description, $visibility, $homepage, $license, 'main']
                );

                $id = (int) $pdo->lastInsertId();
                if ($id < 1) {
                    throw new RuntimeException('Database did not return an ID for the new repository.');
                }

                $path = self::path($id);
                $parent = dirname($path);
                if (!is_dir($parent) && !@mkdir($parent, 0775, true) && !is_dir($parent)) {
                    throw new RuntimeException('Unable to create repository storage directory: ' . $parent);
                }
                if (file_exists($path) || is_link($path)) {
                    throw new RuntimeException('Repository path already exists: ' . $path);
                }
                $cleanPathOnFailure = true;

                $git = new GitService($path);
                self::requireSuccess(
                    $git->run(['init', '--bare', '--initial-branch=main', '--', $path], $parent),
                    'Unable to initialize bare repository'
                );
                self::requireSuccess(
                    $git->run(['config', '--local', 'user.name', 'WikiCode']),
                    'Unable to configure repository user name'
                );
                self::requireSuccess(
                    $git->run(['config', '--local', 'user.email', 'wikicode@localhost']),
                    'Unable to configure repository user email'
                );
                self::requireSuccess(
                    $git->run(['config', '--local', 'http.receivepack', 'true']),
                    'Unable to enable Git push over HTTP'
                );
                self::createInitialCommit($git, $name, $description, $license, (string) $owner['username']);

                return $id;
            });
        } catch (Throwable $exception) {
            if ($cleanPathOnFailure && $path !== null && (file_exists($path) || is_link($path))) {
                try {
                    self::removeDirectory($path);
                } catch (Throwable $cleanupException) {
                    throw new RuntimeException(
                        'Repository creation failed and disk cleanup also failed: ' . $cleanupException->getMessage(),
                        0,
                        $exception
                    );
                }
            }
            throw $exception;
        }

        $repository = self::findById($id);
        if ($repository === null) {
            throw new RuntimeException('New repository could not be loaded after creation: ' . $id);
        }
        return $repository;
    }

    public static function update(int $id, string $name, string $description, string $visibility, string $homepage = ''): array
    {
        self::assertPositiveId($id);
        self::assertValidRepositoryName($name);
        self::assertValidDescription($description);
        self::assertValidVisibility($visibility);
        if ($homepage !== '') {
            self::assertValidHomepage($homepage);
        }

        if (self::findById($id) === null) {
            throw new RuntimeException('Repository does not exist: ' . $id);
        }

        Database::execute(
            'UPDATE repositories SET name = ?, description = ?, visibility = ?, homepage = ? WHERE id = ?',
            [$name, $description, $visibility, $homepage, $id]
        );

        $repository = self::findById($id);
        if ($repository === null) {
            throw new RuntimeException('Repository could not be loaded after update: ' . $id);
        }
        return $repository;
    }

    public static function delete(int $id): void
    {
        self::assertPositiveId($id);
        Database::execute('DELETE FROM repositories WHERE id = ?', [$id]);
        self::removeDirectory(self::path($id));
    }

    public static function path(int $id): string
    {
        self::assertPositiveId($id);
        return dirname(__DIR__, 2) . '/storage/repositories/' . $id . '.git';
    }

    private static function createInitialCommit(GitService $git, string $name, string $description, string $licenseId = '', string $author = ''): void
    {
        $readme = '# ' . $name . "\n\n";
        if ($description !== '') {
            $readme .= $description . "\n\n";
        }
        $readme .= "此仓库由 WikiCode 创建，欢迎使用。\n";

        $files = [['100644', 'README.md', $readme]];

        if ($licenseId !== '' && LicenseService::exists($licenseId)) {
            $licenseBody = LicenseService::render($licenseId, [
                'project' => $name,
                'author' => $author,
            ]);
            $files[] = ['100644', 'LICENSE', $licenseBody];
        }

        $treeLines = '';
        $entries = [];
        foreach ($files as [$mode, $filename, $content]) {
            $blobResult = $git->run(['hash-object', '-w', '--stdin'], null, $content);
            $blobSha = self::gitObjectSha($blobResult, 'Unable to write blob for ' . $filename);
            $treeLines .= $mode . ' blob ' . $blobSha . "\t" . $filename . "\n";
        }
        $tree = self::gitObjectSha(
            $git->run(['mktree'], null, $treeLines),
            'Unable to create initial tree'
        );
        $commit = self::gitObjectSha(
            $git->run(['commit-tree', $tree], null, "初始提交\n"),
            'Unable to create initial commit'
        );

        self::requireSuccess(
            $git->run(['update-ref', 'refs/heads/main', $commit]),
            'Unable to set main branch to initial commit'
        );
    }

    /** @param array{success: bool, stdout: string, stderr: string, exit_code: int} $result */
    private static function gitObjectSha(array $result, string $message): string
    {
        self::requireSuccess($result, $message);
        $sha = strtolower(trim($result['stdout']));
        if (!preg_match('/^[0-9a-f]{40}$/', $sha)) {
            throw new RuntimeException($message . ': Git returned an invalid object ID.');
        }
        return $sha;
    }

    /** @param array{success: bool, stdout: string, stderr: string, exit_code: int} $result */
    private static function requireSuccess(array $result, string $message): void
    {
        if ($result['success']) {
            return;
        }

        $error = trim($result['stderr']);
        throw new RuntimeException(
            $message . ': ' . ($error !== '' ? $error : 'Git exited with code ' . $result['exit_code'] . '.')
        );
    }

    private static function assertValidUsername(string $username): void
    {
        if (!preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9_-]{0,30}[A-Za-z0-9])?$/', $username)) {
            throw new InvalidArgumentException('Owner username contains unsafe characters.');
        }
    }

    private static function assertValidRepositoryName(string $name): void
    {
        if (!preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9._-]{0,48}[A-Za-z0-9])?$/', $name) || str_ends_with(strtolower($name), '.git')) {
            throw new InvalidArgumentException('Repository name must be 1-50 safe ASCII characters and must not end with .git.');
        }
    }

    private static function assertValidDescription(string $description): void
    {
        if (!preg_match('/^.{0,255}$/us', $description)) {
            throw new InvalidArgumentException('Description must be valid UTF-8 and at most 255 characters.');
        }
    }

    private static function assertValidVisibility(string $visibility): void
    {
        if (!in_array($visibility, ['public', 'private'], true)) {
            throw new InvalidArgumentException('Repository visibility must be public or private.');
        }
    }

    private static function assertValidHomepage(string $homepage): void
    {
        if (strlen($homepage) > 255) {
            throw new InvalidArgumentException('Homepage URL must not exceed 255 characters.');
        }
        if (!preg_match('#^https?://[^\s<>"\']+$#i', $homepage)) {
            throw new InvalidArgumentException('Homepage must be a valid http:// or https:// URL.');
        }
    }

    private static function assertPositiveId(int $id): void
    {
        if ($id < 1) {
            throw new InvalidArgumentException('ID must be a positive integer.');
        }
    }

    // ———————————————— Star / Watch / Fork 统计与操作 ————————————————

    public static function starCount(int $repositoryId): int
    {
        return (int) Database::fetch('SELECT COUNT(*) AS c FROM stars WHERE repository_id = ?', [$repositoryId])['c'];
    }

    public static function watchCount(int $repositoryId): int
    {
        return (int) Database::fetch('SELECT COUNT(*) AS c FROM watchers WHERE repository_id = ?', [$repositoryId])['c'];
    }

    public static function forkCount(int $repositoryId): int
    {
        return (int) Database::fetch('SELECT COUNT(*) AS c FROM forks WHERE repository_id = ?', [$repositoryId])['c'];
    }

    public static function isStarred(int $repositoryId, int $userId): bool
    {
        return (bool) Database::fetch('SELECT 1 FROM stars WHERE repository_id = ? AND user_id = ?', [$repositoryId, $userId]);
    }

    public static function isWatched(int $repositoryId, int $userId): bool
    {
        return (bool) Database::fetch('SELECT 1 FROM watchers WHERE repository_id = ? AND user_id = ?', [$repositoryId, $userId]);
    }

    public static function toggleStar(int $repositoryId, int $userId): bool
    {
        if (self::isStarred($repositoryId, $userId)) {
            Database::execute('DELETE FROM stars WHERE repository_id = ? AND user_id = ?', [$repositoryId, $userId]);
            return false;
        }
        Database::execute('INSERT IGNORE INTO stars (repository_id, user_id) VALUES (?, ?)', [$repositoryId, $userId]);
        return true;
    }

    public static function toggleWatch(int $repositoryId, int $userId): bool
    {
        if (self::isWatched($repositoryId, $userId)) {
            Database::execute('DELETE FROM watchers WHERE repository_id = ? AND user_id = ?', [$repositoryId, $userId]);
            return false;
        }
        Database::execute('INSERT IGNORE INTO watchers (repository_id, user_id) VALUES (?, ?)', [$repositoryId, $userId]);
        return true;
    }

    private static function removeDirectory(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            if (!@unlink($path) && (file_exists($path) || is_link($path))) {
                throw new RuntimeException('Unable to remove repository path: ' . $path);
            }
            return;
        }
        if (!is_dir($path)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $itemPath = $item->getPathname();
            if ($item->isLink() || $item->isFile()) {
                if (!@unlink($itemPath) && (file_exists($itemPath) || is_link($itemPath))) {
                    throw new RuntimeException('Unable to delete repository file: ' . $itemPath);
                }
            } elseif ($item->isDir() && !@rmdir($itemPath) && is_dir($itemPath)) {
                throw new RuntimeException('Unable to delete repository directory: ' . $itemPath);
            }
        }

        if (!@rmdir($path) && is_dir($path)) {
            throw new RuntimeException('Unable to delete repository: ' . $path);
        }
    }
}
