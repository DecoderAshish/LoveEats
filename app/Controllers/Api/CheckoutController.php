<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Bootstrap\App;
use App\Http\Request;
use App\Services\Checkout\CheckoutService;
use App\Support\ApiResponse;
use App\Support\Auth;
use App\Support\Database;

final class CheckoutController extends BaseApiController
{
    private function service(App $app): CheckoutService
    {
        return new CheckoutService(Database::pdo($app->config()));
    }

    public function preview(Request $request, App $app, array $params): \App\Http\Response
    {
        $uid = Auth::userId();
        if ($uid === null) {
            return ApiResponse::error('UNAUTHENTICATED', 'Authentication required', 401);
        }
        $addressId = isset($request->body['address_id']) ? (int)$request->body['address_id'] : null;
        $data = $this->service($app)->preview($uid, $addressId);
        return ApiResponse::ok($data);
    }

    public function placeOrder(Request $request, App $app, array $params): \App\Http\Response
    {
        $uid = Auth::userId();
        if ($uid === null) {
            return ApiResponse::error('UNAUTHENTICATED', 'Authentication required', 401);
        }
        $addressId = isset($request->body['address_id']) ? (int)$request->body['address_id'] : 0;
        $method = strtolower(trim((string)($request->body['payment_method'] ?? 'upi')));
        $walletUse = isset($request->body['wallet_use']) ? (float)$request->body['wallet_use'] : 0.0;
        if ($addressId <= 0) {
            return ApiResponse::error('VALIDATION_ERROR', 'address_id is required', 422);
        }
        if (!in_array($method, ['upi', 'cod', 'wallet'], true)) {
            return ApiResponse::error('VALIDATION_ERROR', 'Invalid payment_method', 422);
        }
        try {
            $res = $this->service($app)->placeOrder($uid, $addressId, $method, $walletUse);
            return ApiResponse::ok($res);
        } catch (\Throwable $e) {
            return ApiResponse::error('CHECKOUT_FAILED', $e->getMessage(), 400);
        }
    }
}
