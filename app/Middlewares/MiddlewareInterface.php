<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Bootstrap\App;
use App\Http\Request;
use App\Http\Response;

interface MiddlewareInterface
{
    /** @param callable(Request, App): Response $next */
    public function handle(Request $request, App $app, callable $next): Response;
}
