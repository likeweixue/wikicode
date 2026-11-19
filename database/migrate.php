<?php

declare(strict_types=1);

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $path = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($path)) {
            require $path;
        }
    }
});

Database::waitForConnection();
$pdo = Database::pdo();
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        version VARCHAR(100) PRIMARY KEY,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$applied = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$applied = array_flip($applied);
$files = glob(__DIR__ . '/migrations/*.sql') ?: [];
sort($files);

foreach ($files as $file) {
    $version = basename($file, '.sql');
    if (isset($applied[$version])) {
        continue;
    }
    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException("Unable to read migration {$version}");
    }
    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
    foreach ($statements as $statement) {
        if (trim($statement) !== '') {
            $pdo->exec($statement);
        }
    }
    $insert = $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)');
    $insert->execute([$version]);
    fwrite(STDOUT, "Applied migration: {$version}\n");
}

$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($userCount === 0) {
    $username = getenv('ADMIN_USERNAME') ?: 'admin';
    $email = getenv('ADMIN_EMAIL') ?: 'admin@wikicode.local';
    $password = getenv('ADMIN_PASSWORD') ?: 'WikiCode2026!';
    if (strlen($password) < 12) {
        throw new RuntimeException('ADMIN_PASSWORD must contain at least 12 characters.');
    }
    $statement = $pdo->prepare(
        'INSERT INTO users (username, email, password, role, locale, theme) VALUES (?, ?, ?, \'admin\', \'zh-CN\', \'light\')'
    );
    $statement->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
    fwrite(STDOUT, "Created initial administrator: {$username}\n");
}

$defaults = [
    'site_name' => 'wikicode',
    'site_description' => '一起搭建的代码仓库',
    'default_language' => 'zh-CN',
    'allow_registration' => '1',
    'explore_hot_title' => '热门',
    'explore_hot_mode' => 'stars',
    'explore_featured_repos' => '',
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_encryption' => 'tls',
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_from_address' => '',
    'smtp_from_name' => 'wikicode',
];
$statement = $pdo->prepare('INSERT IGNORE INTO settings (`key`, `value`) VALUES (?, ?)');
foreach ($defaults as $key => $value) {
    $statement->execute([$key, $value]);
}

fwrite(STDOUT, "wikicode database is ready.\n");
