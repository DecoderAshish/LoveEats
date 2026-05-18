<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Bootstrap\App;
use App\Http\Request;
use App\Http\Response;

final class SessionMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, App $app, callable $next): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_name('loveeats_session');
            session_start();
        }

        return $next($request, $app);
    }
}
