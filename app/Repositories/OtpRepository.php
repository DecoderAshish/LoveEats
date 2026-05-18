<?php
declare(strict_types=1);

namespace App\Repositories;

final class OtpRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function create(string $phone, string $codeHash, int $ttlSeconds): int
    {
        $st = $this->pdo->prepare('INSERT INTO otp_requests (phone, code_hash, expires_at, attempts, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), 0, NOW())');
        $st->execute([$phone, $codeHash, $ttlSeconds]);
        return (int)$this->pdo->lastInsertId();
    }

    public function latestValid(string $phone): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM otp_requests WHERE phone = ? ORDER BY id DESC LIMIT 1');
        $st->execute([$phone]);
        $row = $st->fetch();
        if (!is_array($row)) {
            return null;
        }
        return $row;
    }

    public function incrementAttempts(int $id): void
    {
        $st = $this->pdo->prepare('UPDATE otp_requests SET attempts = attempts + 1 WHERE id = ?');
        $st->execute([$id]);
    }
}

