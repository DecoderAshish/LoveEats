<?php
declare(strict_types=1);

namespace App\Support;

use App\Http\Response;

final class ApiResponse
{
    public static function ok(mixed $data = null, array $meta = []): Response
    {
        return Response::json([
            'success' => true,
            'data' => $data,
            'meta' => array_merge(['request_id' => self::requestId()], $meta),
            'error' => null,
        ]);
    }

    public static function error(string $code, string $message, int $status, mixed $details = null): Response
    {
        return Response::json([
            'success' => false,
            'data' => null,
            'meta' => ['request_id' => self::requestId()],
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status);
    }

    private static function requestId(): string
    {
        static $id = null;
        if ($id === null) {
            $id = bin2hex(random_bytes(8));
        }
        return $id;
    }
}
