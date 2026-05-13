<?php
declare(strict_types=1);

namespace App\Controllers\VendorApi;

use App\Bootstrap\App;
use App\Http\Request;
use App\Support\ApiResponse;
use App\Support\Database;
use App\Support\Str;

final class VendorMenuController
{
    public function list(Request $request, App $app, array $params): \App\Http\Response
    {
        $restaurantId = isset($request->query['restaurant_id']) ? (int)$request->query['restaurant_id'] : 0;
        if ($restaurantId <= 0) {
            return ApiResponse::error('VALIDATION_ERROR', 'restaurant_id is required', 422);
        }
        $pdo = Database::pdo($app->config());
        $st = $pdo->prepare('SELECT id, food_category_id, name, slug, base_price, is_active, is_veg, spicy_level FROM food_items WHERE restaurant_id = ? AND deleted_at IS NULL ORDER BY id DESC LIMIT 200');
        $st->execute([$restaurantId]);
        $rows = $st->fetchAll();
        return ApiResponse::ok(['items' => is_array($rows) ? $rows : []]);
    }

    public function createItem(Request $request, App $app, array $params): \App\Http\Response
    {
        $restaurantId = isset($request->body['restaurant_id']) ? (int)$request->body['restaurant_id'] : 0;
        $name = trim((string)($request->body['name'] ?? ''));
        $price = isset($request->body['base_price']) ? (float)$request->body['base_price'] : 0.0;
        $categoryId = isset($request->body['food_category_id']) ? (int)$request->body['food_category_id'] : null;
        if ($restaurantId <= 0 || $name === '' || $price <= 0) {
            return ApiResponse::error('VALIDATION_ERROR', 'restaurant_id, name, base_price are required', 422);
        }
        $slug = Str::slug($name);
        $pdo = Database::pdo($app->config());
        $st = $pdo->prepare('INSERT INTO food_items (restaurant_id, food_category_id, name, slug, base_price, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())');
        $st->execute([$restaurantId, $categoryId, $name, $slug, $price]);
        return ApiResponse::ok(['id' => (int)$pdo->lastInsertId()]);
    }

    public function updateItem(Request $request, App $app, array $params): \App\Http\Response
    {
        $restaurantId = isset($request->body['restaurant_id']) ? (int)$request->body['restaurant_id'] : 0;
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        if ($restaurantId <= 0 || $id <= 0) {
            return ApiResponse::error('VALIDATION_ERROR', 'restaurant_id and id are required', 422);
        }
        $fields = [];
        $values = [];
        foreach (['name', 'description'] as $f) {
            if (array_key_exists($f, $request->body)) {
                $fields[] = "{$f} = ?";
                $values[] = $request->body[$f] !== '' ? (string)$request->body[$f] : null;
            }
        }
        if (array_key_exists('base_price', $request->body)) {
            $fields[] = 'base_price = ?';
            $values[] = (float)$request->body['base_price'];
        }
        if (array_key_exists('is_active', $request->body)) {
            $fields[] = 'is_active = ?';
            $values[] = (int)(bool)$request->body['is_active'];
        }
        if ($fields === []) {
            return ApiResponse::error('VALIDATION_ERROR', 'No fields to update', 422);
        }
        $values[] = $id;
        $values[] = $restaurantId;
        $pdo = Database::pdo($app->config());
        $sql = 'UPDATE food_items SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = ? AND restaurant_id = ?';
        $pdo->prepare($sql)->execute($values);
        return ApiResponse::ok(['status' => 'ok']);
    }
}

