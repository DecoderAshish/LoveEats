<?php
declare(strict_types=1);

namespace App\Repositories;

final class SessionRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function create(int $userId, string $tokenHash, ?string $ip, ?string $userAgent): int
    {
        $st = $this->pdo->prepare('INSERT INTO sessions (user_id, token_hash, ip, user_agent, created_at, last_seen_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
        $st->execute([$userId, $tokenHash, $ip, $userAgent]);
        return (int)$this->pdo->lastInsertId();
    }

    public function touch(int $sessionId): void
    {
        $st = $this->pdo->prepare('UPDATE sessions SET last_seen_at = NOW() WHERE id = ?');
        $st->execute([$sessionId]);
    }

    public function revoke(int $sessionId, int $userId): void
    {
        $st = $this->pdo->prepare('UPDATE sessions SET revoked_at = NOW() WHERE id = ? AND user_id = ?');
        $st->execute([$sessionId, $userId]);
    }

    /** @return list<array<string, mixed>> */
    public function listForUser(int $userId): array
    {
        $st = $this->pdo->prepare('SELECT id, ip, user_agent, created_at, last_seen_at, revoked_at FROM sessions WHERE user_id = ? ORDER BY id DESC LIMIT 30');
        $st->execute([$userId]);
        $rows = $st->fetchAll();
        return is_array($rows) ? $rows : [];
    }
}

