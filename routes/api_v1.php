<?php
declare(strict_types=1);

use App\Controllers\Api\AuthController;
use App\Controllers\Api\RestaurantController;
use App\Controllers\Api\CartController;
use App\Controllers\Api\CheckoutController;
use App\Controllers\Api\OrderController;
use App\Controllers\Api\TrackingController;
use App\Controllers\Api\WalletController;
use App\Controllers\Api\ReferralController;
use App\Controllers\Api\CouponController;
use App\Controllers\Api\UserController;
use App\Controllers\Api\NotificationController;
use App\Controllers\Api\ReelsController;
use App\Middlewares\RateLimitMiddleware;
use App\Middlewares\JwtAuthMiddleware;
use App\Middlewares\SecurityHeadersMiddleware;

$router->group('/api/v1', [SecurityHeadersMiddleware::class, RateLimitMiddleware::class], static function ($router): void {
    $auth = new AuthController();
    $router->post('/auth/register', [$auth, 'register']);
    $router->post('/auth/login', [$auth, 'login']);
    $router->post('/auth/otp/request', [$auth, 'otpRequest']);
    $router->post('/auth/otp/verify', [$auth, 'otpVerify']);
    $router->post('/auth/forgot-password', [$auth, 'forgotPassword']);
    $router->post('/auth/reset-password', [$auth, 'resetPassword']);
    $router->post('/auth/logout', [$auth, 'logout'], [JwtAuthMiddleware::class]);
    $router->get('/auth/me', [$auth, 'me'], [JwtAuthMiddleware::class]);
    $router->get('/auth/sessions', [$auth, 'sessions'], [JwtAuthMiddleware::class]);
    $router->delete('/auth/sessions/{id}', [$auth, 'revokeSession'], [JwtAuthMiddleware::class]);

    $restaurants = new RestaurantController();
    $router->get('/cities', [$restaurants, 'cities']);
    $router->get('/restaurants', [$restaurants, 'list']);
    $router->get('/restaurants/slug/{slug}', [$restaurants, 'detailsBySlug']);
    $router->get('/restaurants/{id}', [$restaurants, 'details']);
    $router->get('/restaurants/{id}/menu', [$restaurants, 'menu']);
    $router->get('/search', [$restaurants, 'search']);
    $router->get('/search/smart', [$restaurants, 'smartSearch']);

    $reels = new ReelsController();
    $router->get('/reels', [$reels, 'feed']);

    $cart = new CartController();
    $router->get('/cart', [$cart, 'get'], [JwtAuthMiddleware::class]);
    $router->post('/cart/items', [$cart, 'addItem'], [JwtAuthMiddleware::class]);
    $router->patch('/cart/items/{id}', [$cart, 'updateItem'], [JwtAuthMiddleware::class]);
    $router->delete('/cart/items/{id}', [$cart, 'removeItem'], [JwtAuthMiddleware::class]);
    $router->post('/cart/apply-coupon', [$cart, 'applyCoupon'], [JwtAuthMiddleware::class]);

    $checkout = new CheckoutController();
    $router->post('/checkout/preview', [$checkout, 'preview'], [JwtAuthMiddleware::class]);
    $router->post('/checkout/place-order', [$checkout, 'placeOrder'], [JwtAuthMiddleware::class]);

    $orders = new OrderController();
    $router->get('/orders', [$orders, 'list'], [JwtAuthMiddleware::class]);
    $router->get('/orders/{id}', [$orders, 'details'], [JwtAuthMiddleware::class]);
    $router->post('/orders/{id}/reorder', [$orders, 'reorder'], [JwtAuthMiddleware::class]);
    $router->post('/orders/{id}/support', [$orders, 'createSupportTicket'], [JwtAuthMiddleware::class]);

    $tracking = new TrackingController();
    $router->get('/orders/{id}/tracking', [$tracking, 'snapshot'], [JwtAuthMiddleware::class]);

    $wallet = new WalletController();
    $router->get('/wallet', [$wallet, 'summary'], [JwtAuthMiddleware::class]);
    $router->get('/wallet/transactions', [$wallet, 'transactions'], [JwtAuthMiddleware::class]);

    $referrals = new ReferralController();
    $router->post('/referrals/invite', [$referrals, 'invite'], [JwtAuthMiddleware::class]);
    $router->get('/referrals', [$referrals, 'dashboard'], [JwtAuthMiddleware::class]);

    $coupons = new CouponController();
    $router->get('/coupons', [$coupons, 'list'], [JwtAuthMiddleware::class]);
    $router->post('/coupons/validate', [$coupons, 'validate'], [JwtAuthMiddleware::class]);

    $user = new UserController();
    $router->get('/profile', [$user, 'getProfile'], [JwtAuthMiddleware::class]);
    $router->patch('/profile', [$user, 'updateProfile'], [JwtAuthMiddleware::class]);
    $router->post('/profile/avatar', [$user, 'uploadAvatar'], [JwtAuthMiddleware::class]);
    $router->get('/addresses', [$user, 'addresses'], [JwtAuthMiddleware::class]);
    $router->post('/addresses', [$user, 'createAddress'], [JwtAuthMiddleware::class]);
    $router->patch('/addresses/{id}', [$user, 'updateAddress'], [JwtAuthMiddleware::class]);
    $router->delete('/addresses/{id}', [$user, 'deleteAddress'], [JwtAuthMiddleware::class]);

    $notifications = new NotificationController();
    $router->get('/notifications', [$notifications, 'list'], [JwtAuthMiddleware::class]);
    $router->post('/notifications/{id}/read', [$notifications, 'markRead'], [JwtAuthMiddleware::class]);
});
