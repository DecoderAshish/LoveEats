<?php
declare(strict_types=1);

namespace App\Repositories;

final class WalletRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function ensureWallet(int $userId): int
    {
        $this->pdo->prepare('INSERT IGNORE INTO wallets (user_id, balance) VALUES (?, 0)')->execute([$userId]);
        $st = $this->pdo->prepare('SELECT id FROM wallets WHERE user_id = ?');
        $st->execute([$userId]);
        return (int)$st->fetchColumn();
    }
}

