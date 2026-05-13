<?php
declare(strict_types=1);

namespace App\Support;

final class Str
{
    public static function random(int $bytes = 16): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function slug(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? $value;
        $value = trim($value, '-');
        return $value === '' ? self::random(6) : $value;
    }
}
