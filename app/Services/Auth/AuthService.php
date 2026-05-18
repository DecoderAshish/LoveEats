<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Repositories\OtpRepository;
use App\Repositories\SessionRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use App\Support\Str;

final class AuthService
{
    public function __construct(
        private readonly \PDO $pdo,
        private readonly JwtService $jwt,
        private readonly UserRepository $users,
        private readonly SessionRepository $sessions,
        private readonly OtpRepository $otps,
        private readonly WalletRepository $wallets,
    ) {}

    /** @return array{user:array<string,mixed>, access_token:string, token_type:string} */
    public function registerEmail(string $name, string $email, string $password, ?string $referralCode = null): array
    {
        $existing = $this->users->findByEmail($email);
        if ($existing !== null) {
            throw new \RuntimeException('Email already registered');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $newReferral = $this->generateReferralCode();
        $userId = $this->users->createUser($name, $email, null, $hash, $newReferral);
        $this->users->attachRole($userId, 'customer');
        $this->wallets->ensureWallet($userId);

        $this->createEmailVerification($userId);
        $sessionId = $this->createSession($userId);
        $role = $this->primaryRole($userId);
        $token = $this->jwt->encode(['uid' => $userId, 'role' => $role, 'sid' => $sessionId], 60 * 60 * 24 * 30);

        $user = $this->users->findById($userId);
        return [
            'user' => $user ?? ['id' => $userId],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    /** @return array{user:array<string,mixed>, access_token:string, token_type:string} */
    public function loginEmail(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);
        if ($user === null || ($user['password_hash'] ?? null) === null) {
            throw new \RuntimeException('Invalid credentials');
        }
        if (!password_verify($password, (string)$user['password_hash'])) {
            throw new \RuntimeException('Invalid credentials');
        }

        $sessionId = $this->createSession((int)$user['id']);
        $role = $this->primaryRole((int)$user['id']);
        $token = $this->jwt->encode(['uid' => (int)$user['id'], 'role' => $role, 'sid' => $sessionId], 60 * 60 * 24 * 30);
        return ['user' => $user, 'access_token' => $token, 'token_type' => 'Bearer'];
    }

    /** @return array{otp_request_id:int, expires_in:int} */
    public function requestOtp(string $phone, int $ttlSeconds = 300): array
    {
        $otp = (string)random_int(100000, 999999);
        $hash = password_hash($otp, PASSWORD_DEFAULT);
        $id = $this->otps->create($phone, $hash, $ttlSeconds);
        return ['otp_request_id' => $id, 'expires_in' => $ttlSeconds, 'otp_debug' => $otp];
    }

    /** @return array{user:array<string,mixed>, access_token:string, token_type:string, is_new:bool} */
    public function verifyOtp(string $phone, string $otp): array
    {
        $req = $this->otps->latestValid($phone);
        if ($req === null) {
            throw new \RuntimeException('OTP not found');
        }
        if (strtotime((string)$req['expires_at']) <= time()) {
            throw new \RuntimeException('OTP expired');
        }
        if ((int)$req['attempts'] >= 5) {
            throw new \RuntimeException('Too many attempts');
        }

        if (!password_verify($otp, (string)$req['code_hash'])) {
            $this->otps->incrementAttempts((int)$req['id']);
            throw new \RuntimeException('Invalid OTP');
        }

        $user = $this->users->findByPhone($phone);
        $isNew = false;
        if ($user === null) {
            $isNew = true;
            $userId = $this->users->createUser('Love Eats User', null, $phone, null, $this->generateReferralCode());
            $this->users->attachRole($userId, 'customer');
            $this->wallets->ensureWallet($userId);
            $user = $this->users->findById($userId);
        }

        $userId = (int)($user['id'] ?? 0);
        $sessionId = $this->createSession($userId);
        $role = $this->primaryRole($userId);
        $token = $this->jwt->encode(['uid' => $userId, 'role' => $role, 'sid' => $sessionId], 60 * 60 * 24 * 30);

        return ['user' => $user ?? ['id' => $userId], 'access_token' => $token, 'token_type' => 'Bearer', 'is_new' => $isNew];
    }

    /** @return array{reset_token:string, expires_in:int} */
    public function createPasswordReset(string $email, int $ttlSeconds = 900): array
    {
        $user = $this->users->findByEmail($email);
        if ($user === null) {
            return ['reset_token' => 'sent', 'expires_in' => $ttlSeconds];
        }

        $token = Str::random(24);
        $hash = password_hash($token, PASSWORD_DEFAULT);
        $st = $this->pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NOW())');
        $st->execute([(int)$user['id'], $hash, $ttlSeconds]);
        return ['reset_token' => $token, 'expires_in' => $ttlSeconds];
    }

    public function resetPassword(string $email, string $token, string $newPassword): void
    {
        $user = $this->users->findByEmail($email);
        if ($user === null) {
            throw new \RuntimeException('Invalid token');
        }

        $st = $this->pdo->prepare('SELECT * FROM password_resets WHERE user_id = ? AND used_at IS NULL ORDER BY id DESC LIMIT 1');
        $st->execute([(int)$user['id']]);
        $row = $st->fetch();
        if (!is_array($row)) {
            throw new \RuntimeException('Invalid token');
        }
        if (strtotime((string)$row['expires_at']) <= time()) {
            throw new \RuntimeException('Token expired');
        }
        if (!password_verify($token, (string)$row['token_hash'])) {
            throw new \RuntimeException('Invalid token');
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->pdo->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?')->execute([$hash, (int)$user['id']]);
        $this->pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([(int)$row['id']]);
    }

    public function revokeSession(int $sessionId, int $userId): void
    {
        $this->sessions->revoke($sessionId, $userId);
    }

    /** @return list<array<string,mixed>> */
    public function listSessions(int $userId): array
    {
        return $this->sessions->listForUser($userId);
    }

    private function createSession(int $userId): int
    {
        $token = Str::random(24);
        $tokenHash = password_hash($token, PASSWORD_DEFAULT);
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null;
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : null;
        return $this->sessions->create($userId, $tokenHash, $ip, $ua);
    }

    private function generateReferralCode(): string
    {
        return 'LE' . strtoupper(substr(Str::random(12), 0, 10));
    }

    private function primaryRole(int $userId): string
    {
        $roles = $this->users->rolesForUser($userId);
        $priority = ['admin', 'vendor', 'delivery_partner', 'customer'];
        foreach ($priority as $p) {
            if (in_array($p, $roles, true)) {
                return $p;
            }
        }
        return 'customer';
    }

    private function createEmailVerification(int $userId): void
    {
        $token = Str::random(24);
        $hash = password_hash($token, PASSWORD_DEFAULT);
        $st = $this->pdo->prepare('INSERT INTO email_verifications (user_id, token_hash, expires_at, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 48 HOUR), NOW()) ON DUPLICATE KEY UPDATE token_hash=VALUES(token_hash), expires_at=VALUES(expires_at)');
        $st->execute([$userId, $hash]);
    }
}

