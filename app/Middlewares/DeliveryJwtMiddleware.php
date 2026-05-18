<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Bootstrap\App;
use App\Http\Request;
use App\Http\Response;
use App\Services\Auth\JwtService;
use App\Support\ApiResponse;
use App\Support\DeliveryAuth;

final class DeliveryJwtMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, App $app, callable $next): Response
    {
        $authHeader = $request->header('authorization');
        if ($authHeader === null || !preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
            return ApiResponse::error('UNAUTHENTICATED', 'Authentication required', 401);
        }
        $jwt = new JwtService((string)$app->config()->get('app.jwt_secret'));
        $claims = $jwt->decode(trim($m[1]));
        if ($claims === null || !isset($claims['dpid'])) {
            return ApiResponse::error('UNAUTHENTICATED', 'Invalid token', 401);
        }
        DeliveryAuth::set((int)$claims['dpid']);
        return $next($request, $app);
    }
}

