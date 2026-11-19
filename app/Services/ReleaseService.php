<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

final class ReleaseService
{
    /**
     * 仓库的发行版列表（附带作者与附件数量）。
     *
     * @return list<array<string, mixed>>
     */
    public static function summaries(int $repositoryId, int $limit = 50): array
    {
        $limit = max(1, min($limit, 100));
        return Database::fetchAll(
            'SELECT rel.*, u.username AS author_username, '
            . '(SELECT COUNT(*) FROM release_assets a WHERE a.release_id = rel.id) AS asset_count '
            . 'FROM releases rel LEFT JOIN users u ON u.id = rel.author_id '
            . 'WHERE rel.repository_id = ? ORDER BY rel.created_at DESC, rel.id DESC LIMIT ' . $limit,
            [$repositoryId]
        );
    }

    /** @return array<string, mixed>|null */
    public static function find(int $repositoryId, int $releaseId): ?array
    {
        return Database::fetch(
            'SELECT rel.*, u.username AS author_username FROM releases rel '
            . 'LEFT JOIN users u ON u.id = rel.author_id '
            . 'WHERE rel.repository_id = ? AND rel.id = ? LIMIT 1',
            [$repositoryId, $releaseId]
        );
    }

    /** @return array<string, mixed>|null */
    public static function findByTag(int $repositoryId, string $tag): ?array
    {
        return Database::fetch(
            'SELECT * FROM releases WHERE repository_id = ? AND tag = ? LIMIT 1',
            [$repositoryId, $tag]
        );
    }

    public static function create(int $repositoryId, string $tag, string $title, string $body, ?int $authorId, bool $prerelease): int
    {
        Database::execute(
            'INSERT INTO releases (repository_id, tag, title, body, author_id, is_prerelease, created_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [$repositoryId, $tag, $title, $body, $authorId, $prerelease ? 1 : 0]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    /** @return list<array<string, mixed>> */
    public static function assets(int $releaseId): array
    {
        return Database::fetchAll(
            'SELECT * FROM release_assets WHERE release_id = ? ORDER BY created_at ASC, id ASC',
            [$releaseId]
        );
    }

    /** @return array<string, mixed>|null */
    public static function findAsset(int $releaseId, int $assetId): ?array
    {
        return Database::fetch(
            'SELECT * FROM release_assets WHERE id = ? AND release_id = ? LIMIT 1',
            [$assetId, $releaseId]
        );
    }

    public static function incrementAssetDownload(int $assetId): void
    {
        Database::execute('UPDATE release_assets SET downloads = downloads + 1 WHERE id = ?', [$assetId]);
    }

    public static function assetDir(int $repositoryId, int $releaseId): string
    {
        return dirname(__DIR__, 2) . '/storage/releases/' . $repositoryId . '/' . $releaseId;
    }

    /**
     * 保存一个上传附件（$_FILES 中的单条）。
     */
    public static function storeAsset(int $repositoryId, int $releaseId, array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed with code ' . $error);
        }

        $name = (string) ($file['name'] ?? '');
        $name = str_replace(["\0", '/', '\\'], '_', $name);
        $name = (string) preg_replace('/[\x00-\x1f\x7f]/u', '', $name);
        $name = trim(mb_substr($name, 0, 180, 'UTF-8'));
        if ($name === '' || $name === '.' || $name === '..') {
            $name = 'asset.bin';
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Invalid upload.');
        }

        $dir = self::assetDir($repositoryId, $releaseId);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to create the asset directory.');
        }

        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $extension = (string) preg_replace('/[^A-Za-z0-9]/', '', $extension);
        $stored = bin2hex(random_bytes(10)) . ($extension !== '' ? '.' . $extension : '');
        $target = $dir . '/' . $stored;
        if (!move_uploaded_file($tmp, $target)) {
            throw new RuntimeException('Unable to store the uploaded file.');
        }

        Database::execute(
            'INSERT INTO release_assets (release_id, filename, stored_name, size, created_at) VALUES (?, ?, ?, ?, NOW())',
            [$releaseId, $name, $stored, (int) filesize($target)]
        );
    }

    /**
     * 批量保存 $_FILES['assets'] 形式的多文件上传。
     */
    public static function storeUploadedAssets(int $repositoryId, int $releaseId, array $files): void
    {
        $names = $files['name'] ?? null;
        if (is_array($names)) {
            foreach ($names as $index => $name) {
                self::storeAsset($repositoryId, $releaseId, [
                    'name' => $name,
                    'tmp_name' => $files['tmp_name'][$index] ?? '',
                    'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $files['size'][$index] ?? 0,
                    'type' => $files['type'][$index] ?? '',
                ]);
            }
            return;
        }
        if (is_string($names) && $names !== '') {
            self::storeAsset($repositoryId, $releaseId, $files);
        }
    }
}
