<?php
declare(strict_types=1);

namespace App\Http;

final class Request
{
    /** @param array<string, mixed> $query */
    /** @param array<string, mixed> $body */
    /** @param array<string, string> $headers */
    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $headers,
        public readonly array $cookies,
        public readonly array $files,
        public readonly ?string $rawBody,
    ) {}

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';
        $path = '/' . ltrim($path, '/');

        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($k, 5)));
                $headers[$name] = (string)$v;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string)$_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['AUTHORIZATION'])) {
            $headers['authorization'] = (string)$_SERVER['AUTHORIZATION'];
        }

        $rawBody = file_get_contents('php://input');
        $rawBody = $rawBody === false ? null : $rawBody;

        $body = $_POST;
        $contentType = $headers['content-type'] ?? '';
        if ($rawBody !== null && $rawBody !== '' && str_contains($contentType, 'application/json')) {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        return new self(
            $method,
            $path,
            $_GET,
            $body,
            $headers,
            $_COOKIE,
            $_FILES,
            $rawBody,
        );
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $key = strtolower($name);
        return $this->headers[$key] ?? $default;
    }

    public function wantsJson(): bool
    {
        $accept = $this->header('accept', '') ?? '';
        if (str_contains($accept, 'application/json')) {
            return true;
        }
        return str_starts_with($this->path, '/api/') || str_starts_with($this->path, '/admin-api/');
    }
}
