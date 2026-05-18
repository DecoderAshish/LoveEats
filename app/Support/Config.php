<?php
declare(strict_types=1);

namespace App\Support;

final class Config
{
    private string $configPath;
    /** @var array<string, mixed> */
    private array $cache = [];

    public function __construct(string $configPath)
    {
        $this->configPath = rtrim($configPath, '/');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $file = array_shift($segments);
        if ($file === null || $file === '') {
            return $default;
        }

        $data = $this->loadFile($file);
        foreach ($segments as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default;
            }
            $data = $data[$segment];
        }

        return $data ?? $default;
    }

    /** @return array<string, mixed> */
    private function loadFile(string $file): array
    {
        if (array_key_exists($file, $this->cache)) {
            $cached = $this->cache[$file];
            return is_array($cached) ? $cached : [];
        }

        $path = $this->configPath . '/' . $file . '.php';
        if (!is_file($path)) {
            $this->cache[$file] = [];
            return [];
        }

        $config = require $path;
        $this->cache[$file] = is_array($config) ? $config : [];
        return $this->cache[$file];
    }
}
