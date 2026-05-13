<?php
declare(strict_types=1);

namespace App\Repositories;

final class UserRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function findById(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return is_array($row) ? $row : null;
    }

    public function findByEmail(string $email): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1');
        $st->execute([$email]);
        $row = $st->fetch();
        return is_array($row) ? $row : null;
    }

    public function findByPhone(string $phone): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM users WHERE phone = ? AND deleted_at IS NULL LIMIT 1');
        $st->execute([$phone]);
        $row = $st->fetch();
        return is_array($row) ? $row : null;
    }

    public function createUser(string $name, ?string $email, ?string $phone, ?string $passwordHash, string $referralCode): int
    {
        $st = $this->pdo->prepare('INSERT INTO users (name, email, phone, password_hash, referral_code, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $st->execute([$name, $email, $phone, $passwordHash, $referralCode, 'active']);
        return (int)$this->pdo->lastInsertId();
    }

    /** @return list<string> */
    public function rolesForUser(int $userId): array
    {
        $st = $this->pdo->prepare('SELECT r.name FROM roles r INNER JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = ?');
        $st->execute([$userId]);
        $roles = [];
        while ($row = $st->fetch()) {
            if (is_array($row) && isset($row['name'])) {
                $roles[] = (string)$row['name'];
            }
        }
        return $roles;
    }

    public function attachRole(int $userId, string $roleName): void
    {
        $st = $this->pdo->prepare('SELECT id FROM roles WHERE name = ?');
        $st->execute([$roleName]);
        $roleId = $st->fetchColumn();
        if ($roleId === false) {
            throw new \RuntimeException('Role not found: ' . $roleName);
        }
        $ins = $this->pdo->prepare('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)');
        $ins->execute([$userId, (int)$roleId]);
    }
}

