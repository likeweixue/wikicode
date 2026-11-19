<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class DeployTokenService
{
    /** 生成 24 字节 base36 的明文 token（约 33 字符）。只在创建时返回明文，存库只存 sha256 hash。 */
    public static function generate(): string
    {
        $bytes = random_bytes(24);
        return bin2hex($bytes);
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token, false);
    }

    public static function create(int $userId, string $name, string $scope): string
    {
        $plain = self::generate();
        $hash = self::hash($plain);
        Database::execute(
            'INSERT INTO deploy_tokens (user_id, name, scope, token_hash, created_at) VALUES (?, ?, ?, ?, NOW())',
            [$userId, mb_substr(trim($name), 0, 120), $scope === 'read' ? 'read' : 'write', $hash]
        );
        return $plain;
    }

    /** @return array<string, mixed>|null 返回 token 行 + user 身份（username/role），scope 为 write 则可 push */
    public static function authenticate(string $token): ?array
    {
        if ($token === '' || strlen($token) < 20) {
            return null;
        }
        $hash = self::hash($token);
        $row = Database::fetch(
            'SELECT t.*, u.username, u.role, u.is_active FROM deploy_tokens t '
            . 'LEFT JOIN users u ON u.id = t.user_id '
            . 'WHERE t.token_hash = ? LIMIT 1',
            [$hash]
        );
        if (!$row || (int) ($row['is_active'] ?? 0) !== 1) {
            return null;
        }
        return $row;
    }

    public static function markUsed(int $tokenId, ?string $ip): void
    {
        Database::execute(
            'UPDATE deploy_tokens SET last_used_at = NOW(), last_used_ip = ? WHERE id = ?',
            [$ip, $tokenId]
        );
    }

    /** @return list<array<string, mixed>> */
    public static function listForUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT id, name, scope, last_used_at, last_used_ip, created_at FROM deploy_tokens '
            . 'WHERE user_id = ? ORDER BY id DESC',
            [$userId]
        );
    }

    /** @return list<array<string, mixed>> */
    public static function listAll(): array
    {
        return Database::fetchAll(
            'SELECT t.id, t.name, t.scope, t.last_used_at, t.last_used_ip, t.created_at, u.username '
            . 'FROM deploy_tokens t LEFT JOIN users u ON u.id = t.user_id ORDER BY t.id DESC'
        );
    }

    public static function revoke(int $id, int $userId): bool
    {
        return (bool) Database::execute(
            'DELETE FROM deploy_tokens WHERE id = ? AND user_id = ?',
            [$id, $userId]
        );
    }

    public static function revokeAny(int $id): bool
    {
        return (bool) Database::execute('DELETE FROM deploy_tokens WHERE id = ?', [$id]);
    }
}
