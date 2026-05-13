<?php
declare(strict_types=1);

use App\Controllers\VendorApi\VendorAuthController;
use App\Controllers\VendorApi\VendorOrdersController;
use App\Controllers\VendorApi\VendorMenuController;
use App\Middlewares\RateLimitMiddleware;
use App\Middlewares\JwtAuthMiddleware;
use App\Middlewares\VendorOnlyMiddleware;
use App\Middlewares\SecurityHeadersMiddleware;

$router->group('/vendor-api/v1', [SecurityHeadersMiddleware::class, RateLimitMiddleware::class], static function ($router): void {
    $auth = new VendorAuthController();
    $router->post('/auth/login', [$auth, 'login']);

    $router->group('', [JwtAuthMiddleware::class, VendorOnlyMiddleware::class], static function ($router): void {
        $orders = new VendorOrdersController();
        $router->get('/orders', [$orders, 'list']);
        $router->patch('/orders/{id}', [$orders, 'updateStatus']);

        $menu = new VendorMenuController();
        $router->get('/menu', [$menu, 'list']);
        $router->post('/menu/items', [$menu, 'createItem']);
        $router->patch('/menu/items/{id}', [$menu, 'updateItem']);
    });
});
