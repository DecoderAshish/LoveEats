<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Bootstrap\App;
use App\Http\Request;
use App\Support\ApiResponse;
use App\Support\Auth;
use App\Support\Database;

final class CouponController extends BaseApiController
{
    public function list(Request $request, App $app, array $params): \App\Http\Response
    {
        $cityId = isset($request->query['city_id']) ? (int)$request->query['city_id'] : null;
        $pdo = Database::pdo($app->config());
        $where = ['is_active = 1', 'deleted_at IS NULL', '(starts_at IS NULL OR starts_at <= NOW())', '(ends_at IS NULL OR ends_at >= NOW())'];
        $args = [];
        if ($cityId !== null && $cityId > 0) {
            $where[] = '(city_id IS NULL OR city_id = ?)';
            $args[] = $cityId;
        }
        $st = $pdo->prepare('SELECT id, code, title, description, type, value, max_discount, min_order, restaurant_id, city_id, starts_at, ends_at FROM coupons WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT 50');
        $st->execute($args);
        $rows = $st->fetchAll();
        return ApiResponse::ok(['items' => is_array($rows) ? $rows : []]);
    }

    public function validate(Request $request, App $app, array $params): \App\Http\Response
    {
        $code = strtoupper(trim((string)($request->body['code'] ?? '')));
        $subtotal = (float)($request->body['subtotal'] ?? 0);
        $restaurantId = isset($request->body['restaurant_id']) ? (int)$request->body['restaurant_id'] : null;
        if ($code === '') {
            return ApiResponse::error('VALIDATION_ERROR', 'code is required', 422);
        }

        $pdo = Database::pdo($app->config());
        $st = $pdo->prepare('SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND deleted_at IS NULL LIMIT 1');
        $st->execute([$code]);
        $coupon = $st->fetch();
        if (!is_array($coupon)) {
            return ApiResponse::error('INVALID_COUPON', 'Coupon not found', 404);
        }
        if (($coupon['starts_at'] ?? null) !== null && strtotime((string)$coupon['starts_at']) > time()) {
            return ApiResponse::error('INVALID_COUPON', 'Coupon not active yet', 400);
        }
        if (($coupon['ends_at'] ?? null) !== null && strtotime((string)$coupon['ends_at']) < time()) {
            return ApiResponse::error('INVALID_COUPON', 'Coupon expired', 400);
        }
        if (($coupon['min_order'] ?? null) !== null && $subtotal < (float)$coupon['min_order']) {
            return ApiResponse::error('INVALID_COUPON', 'Minimum order not met', 400, ['min_order' => (float)$coupon['min_order']]);
        }
        if (($coupon['restaurant_id'] ?? null) !== null && $restaurantId !== null && (int)$coupon['restaurant_id'] !== $restaurantId) {
            return ApiResponse::error('INVALID_COUPON', 'Coupon not valid for this restaurant', 400);
        }

        if (($coupon['type'] ?? '') === 'first_order') {
            $uid = Auth::userId();
            if ($uid === null) {
                return ApiResponse::error('UNAUTHENTICATED', 'Authentication required', 401);
            }
            $c = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
            $c->execute([$uid]);
            $count = (int)$c->fetchColumn();
            if ($count > 0) {
                return ApiResponse::error('INVALID_COUPON', 'Only valid for first order', 400);
            }
        }

        $type = (string)$coupon['type'];
        $discount = 0.0;
        if ($type === 'percent') {
            $discount = $subtotal * ((float)$coupon['value'] / 100.0);
            if (($coupon['max_discount'] ?? null) !== null) {
                $discount = min($discount, (float)$coupon['max_discount']);
            }
        } elseif ($type === 'flat') {
            $discount = min($subtotal, (float)$coupon['value']);
        } elseif ($type === 'free_delivery') {
            $discount = 0.0;
        }

        return ApiResponse::ok([
            'coupon' => [
                'id' => (int)$coupon['id'],
                'code' => (string)$coupon['code'],
                'type' => $type,
            ],
            'discount' => round($discount, 2),
            'free_delivery' => $type === 'free_delivery',
        ]);
    }
}
