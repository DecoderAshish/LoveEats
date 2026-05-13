<?php
declare(strict_types=1);

namespace App\Support;

final class DeliveryAuth
{
    private static ?int $partnerId = null;

    public static function set(?int $partnerId): void
    {
        self::$partnerId = $partnerId;
    }

    public static function partnerId(): ?int
    {
        return self::$partnerId;
    }
}

