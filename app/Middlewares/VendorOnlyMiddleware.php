<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Bootstrap\App;
use App\Http\Request;
use App\Http\Response;
use App\Support\ApiResponse;
use App\Support\Auth;

final class VendorOnlyMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, App $app, callable $next): Response
    {
        if (Auth::role() !== 'vendor') {
            return ApiResponse::error('FORBIDDEN', 'Vendor role required', 403);
        }
        return $next($request, $app);
    }
}

