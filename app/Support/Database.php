<?php
declare(strict_types=1);

namespace App\Support;

final class Database
{
    private static ?\PDO $pdo = null;

    public static function pdo(Config $config): \PDO
    {
        if (self::$pdo instanceof \PDO) {
            return self::$pdo;
        }

        $db = $config->get('database');
        if (!is_array($db)) {
            throw new \RuntimeException('Database config not found.');
        }

        $dsn = (string)($db['dsn'] ?? '');
        $username = (string)($db['username'] ?? '');
        $password = (string)($db['password'] ?? '');
        $options = is_array($db['options'] ?? null) ? $db['options'] : [];

        self::$pdo = new \PDO($dsn, $username, $password, $options);
        return self::$pdo;
    }
}
