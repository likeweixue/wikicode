<?php

declare(strict_types=1);

namespace App\Core;

final class Setting
{
    private static ?array $cache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        return array_key_exists($key, self::$cache) ? self::$cache[$key] : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        Database::execute(
            'INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
            [$key, $value]
        );
        self::$cache = null;
    }

    public static function all(): array
    {
        self::load();
        return self::$cache;
    }

    private static function load(): void
    {
        if (self::$cache !== null) {
            return;
        }
        self::$cache = [];
        foreach (Database::fetchAll('SELECT `key`, `value` FROM settings') as $row) {
            self::$cache[$row['key']] = $row['value'];
        }
    }
}
