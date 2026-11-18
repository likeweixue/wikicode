<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use RuntimeException;
use Throwable;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = getenv('DB_HOST') ?: 'mysql';
        $port = getenv('DB_PORT') ?: '3306';
        $database = getenv('DB_DATABASE') ?: 'wikicode';
        $username = getenv('DB_USERNAME') ?: 'wikicode';
        $password = getenv('DB_PASSWORD') ?: '';

        self::$pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]
        );

        return self::$pdo;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $statement = self::query($sql, $params);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function execute(string $sql, array $params = []): int
    {
        return self::query($sql, $params)->rowCount();
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $statement = self::pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(is_int($key) ? $key + 1 : ':' . ltrim((string) $key, ':'), $value);
        }
        $statement->execute();
        return $statement;
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public static function waitForConnection(int $attempts = 60): void
    {
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                self::pdo()->query('SELECT 1');
                return;
            } catch (Throwable $exception) {
                if ($attempt === $attempts) {
                    throw new RuntimeException('Database connection failed: ' . $exception->getMessage(), 0, $exception);
                }
                fwrite(STDERR, "Waiting for MySQL ({$attempt}/{$attempts})...\n");
                sleep(2);
                self::$pdo = null;
            }
        }
    }
}
