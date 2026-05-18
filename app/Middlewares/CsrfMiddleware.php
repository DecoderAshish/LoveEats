<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Bootstrap\App;
use App\Http\Request;
use App\Http\Response;
use App\Support\Str;

final class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, App $app, callable $next): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return $next($request, $app);
        }

        $_SESSION['csrf_token'] ??= Str::random(16);

        if ($request->wantsJson()) {
            return $next($request, $app);
        }

        if (in_array($request->method, ['POST', 'PATCH', 'DELETE'], true)) {
            $token = null;
            if (isset($request->body['_csrf'])) {
                $token = (string)$request->body['_csrf'];
            } elseif ($request->header('x-csrf-token') !== null) {
                $token = (string)$request->header('x-csrf-token');
            }

            if (!is_string($token) || !hash_equals((string)$_SESSION['csrf_token'], $token)) {
                return Response::html('Invalid CSRF token.', 419);
            }
        }

        return $next($request, $app);
    }
}
