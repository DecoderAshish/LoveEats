<?php
declare(strict_types=1);

namespace App\Services\Auth;

final class JwtService
{
    public function __construct(private readonly string $secret) {}

    /** @param array<string, mixed> $claims */
    public function encode(array $claims, int $ttlSeconds = 3600): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $now = time();
        $payload = array_merge($claims, [
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
        ]);

        $segments = [
            $this->b64(json_encode($header, JSON_UNESCAPED_SLASHES)),
            $this->b64(json_encode($payload, JSON_UNESCAPED_SLASHES)),
        ];

        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $this->secret, true);
        $segments[] = $this->b64($signature);
        return implode('.', $segments);
    }

    /** @return array<string, mixed>|null */
    public function decode(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        [$h, $p, $s] = $parts;
        $signingInput = $h . '.' . $p;
        $expected = $this->b64(hash_hmac('sha256', $signingInput, $this->secret, true));
        if (!hash_equals($expected, $s)) {
            return null;
        }

        $payloadRaw = $this->b64d($p);
        if ($payloadRaw === null) {
            return null;
        }
        $payload = json_decode($payloadRaw, true);
        if (!is_array($payload)) {
            return null;
        }
        if (isset($payload['exp']) && time() >= (int)$payload['exp']) {
            return null;
        }
        return $payload;
    }

    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function b64d(string $data): ?string
    {
        $data = strtr($data, '-_', '+/');
        $pad = strlen($data) % 4;
        if ($pad > 0) {
            $data .= str_repeat('=', 4 - $pad);
        }
        $decoded = base64_decode($data, true);
        return $decoded === false ? null : $decoded;
    }
}
