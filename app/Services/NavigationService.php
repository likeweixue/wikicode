<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use Throwable;

final class NavigationService
{
    /**
     * 返回当前访客可见的导航项。
     *
     * @return list<array<string, mixed>>
     */
    public static function items(): array
    {
        try {
            $rows = Database::fetchAll('SELECT * FROM nav_items ORDER BY position, id');
        } catch (Throwable) {
            $rows = self::defaults();
        }
        if (!$rows) {
            $rows = self::defaults();
        }

        $user = Auth::user();
        $isAdmin = $user !== null && ($user['role'] ?? '') === 'admin';

        $items = [];
        foreach ($rows as $row) {
            if (!(int) $row['is_visible']) {
                continue;
            }
            if (!empty($row['requires_auth']) && $user === null) {
                continue;
            }
            if (!empty($row['requires_admin']) && !$isAdmin) {
                continue;
            }
            $items[] = $row;
        }
        return $items;
    }

    public static function label(array $item): string
    {
        return $item['label'] !== '' ? $item['label'] : t($item['label_key']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function defaults(): array
    {
        return [
            ['label' => '', 'label_key' => 'Explore', 'url' => '/', 'position' => 1, 'is_visible' => 1, 'requires_auth' => 0, 'requires_admin' => 0, 'is_core' => 1],
            ['label' => '', 'label_key' => 'Community', 'url' => '/community', 'position' => 2, 'is_visible' => 1, 'requires_auth' => 0, 'requires_admin' => 0, 'is_core' => 1],
            ['label' => '', 'label_key' => 'Dashboard', 'url' => '/dashboard', 'position' => 3, 'is_visible' => 1, 'requires_auth' => 1, 'requires_admin' => 0, 'is_core' => 1],
            ['label' => '', 'label_key' => 'New repository', 'url' => '/new', 'position' => 4, 'is_visible' => 1, 'requires_auth' => 1, 'requires_admin' => 0, 'is_core' => 1],
            ['label' => '', 'label_key' => 'Administration', 'url' => '/admin', 'position' => 5, 'is_visible' => 1, 'requires_auth' => 1, 'requires_admin' => 1, 'is_core' => 1],
        ];
    }
}
