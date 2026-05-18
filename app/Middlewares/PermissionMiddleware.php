<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Bootstrap\App;
use App\Http\Request;
use App\Http\Response;
use App\Support\ApiResponse;
use App\Support\Auth;

final class PermissionMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, App $app, callable $next): Response
    {
        $role = Auth::role();
        if ($role !== 'admin') {
            return ApiResponse::error('FORBIDDEN', 'Insufficient permissions', 403);
        }
        return $next($request, $app);
    }
}
