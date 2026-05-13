<?php
declare(strict_types=1);

use App\Support\Env;
use App\Support\Config;
use App\Support\Database;

require dirname(__DIR__) . '/app/Support/Env.php';
Env::load(dirname(__DIR__) . '/.env');

require dirname(__DIR__) . '/app/Bootstrap/App.php';

$config = new Config(dirname(__DIR__) . '/app/Config');
$pdo = Database::pdo($config);

$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT, filename VARCHAR(255) NOT NULL UNIQUE, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

$applied = [];
$stmt = $pdo->query('SELECT filename FROM schema_migrations');
if ($stmt !== false) {
    while ($row = $stmt->fetch()) {
        $applied[(string)$row['filename']] = true;
    }
}

$dir = dirname(__DIR__) . '/database/migrations';
$files = glob($dir . '/*.sql') ?: [];
sort($files, SORT_STRING);

foreach ($files as $file) {
    $name = basename($file);
    if (isset($applied[$name])) {
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException('Failed to read migration: ' . $name);
    }

    $pdo->beginTransaction();
    try {
        $pdo->exec($sql);
        $ins = $pdo->prepare('INSERT INTO schema_migrations (filename) VALUES (?)');
        $ins->execute([$name]);
        $pdo->commit();
        echo "Applied: {$name}\n";
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

echo "Done.\n";
