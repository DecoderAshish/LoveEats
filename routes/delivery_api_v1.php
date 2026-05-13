<?php
declare(strict_types=1);

use App\Controllers\DeliveryApi\DeliveryAuthController;
use App\Controllers\DeliveryApi\DeliveryPartnerController;
use App\Middlewares\RateLimitMiddleware;
use App\Middlewares\DeliveryJwtMiddleware;
use App\Middlewares\SecurityHeadersMiddleware;

$router->group('/dp-api/v1', [SecurityHeadersMiddleware::class, RateLimitMiddleware::class], static function ($router): void {
    $auth = new DeliveryAuthController();
    $router->post('/auth/otp/request', [$auth, 'otpRequest']);
    $router->post('/auth/otp/verify', [$auth, 'otpVerify']);

    $dp = new DeliveryPartnerController();
    $router->group('', [DeliveryJwtMiddleware::class], static function ($router) use ($dp): void {
        $router->post('/me/availability', [$dp, 'setAvailability']);
        $router->post('/location', [$dp, 'updateLocation']);
        $router->get('/orders', [$dp, 'assignedOrders']);
        $router->post('/orders/{id}/status', [$dp, 'updateOrderStatus']);
    });
});
