<?php
declare(strict_types=1);

namespace App\Controllers\AdminApi;

use App\Bootstrap\App;
use App\Http\Request;
use App\Support\ApiResponse;
use App\Support\Database;
use App\Support\Pagination;
use App\Support\Str;

final class RestaurantsController extends BaseAdminApiController
{
    public function list(Request $request, App $app, array $params): \App\Http\Response
    {
        $p = Pagination::fromQuery($request->query);
        $pdo = Database::pdo($app->config());
        $st = $pdo->prepare('SELECT id, name, slug, type, rating, is_active, created_at FROM restaurants WHERE deleted_at IS NULL ORDER BY id DESC LIMIT ' . (int)$p['limit'] . ' OFFSET ' . (int)$p['offset']);
        $st->execute();
        $rows = $st->fetchAll();
        return ApiResponse::ok(['items' => is_array($rows) ? $rows : [], 'pagination' => $p]);
    }

    public function create(Request $request, App $app, array $params): \App\Http\Response
    {
        $name = trim((string)($request->body['name'] ?? ''));
        $type = trim((string)($request->body['type'] ?? 'partner'));
        $cityId = isset($request->body['city_id']) ? (int)$request->body['city_id'] : null;
        if ($name === '') {
            return ApiResponse::error('VALIDATION_ERROR', 'name is required', 422);
        }
        $slug = Str::slug($name);
        $pdo = Database::pdo($app->config());
        $st = $pdo->prepare('INSERT INTO restaurants (city_id, name, slug, type, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())');
        $st->execute([$cityId, $name, $slug, $type]);
        return ApiResponse::ok(['id' => (int)$pdo->lastInsertId()]);
    }

    public function update(Request $request, App $app, array $params): \App\Http\Response
    {
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error('VALIDATION_ERROR', 'Invalid id', 422);
        }
        $fields = [];
        $values = [];
        foreach (['name', 'description', 'type'] as $f) {
            if (array_key_exists($f, $request->body)) {
                $fields[] = "{$f} = ?";
                $values[] = $request->body[$f] !== '' ? (string)$request->body[$f] : null;
            }
        }
        if (array_key_exists('is_active', $request->body)) {
            $fields[] = 'is_active = ?';
            $values[] = (int)(bool)$request->body['is_active'];
        }
        if ($fields === []) {
            return ApiResponse::error('VALIDATION_ERROR', 'No fields to update', 422);
        }
        $values[] = $id;
        $pdo = Database::pdo($app->config());
        $sql = 'UPDATE restaurants SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = ? AND deleted_at IS NULL';
        $pdo->prepare($sql)->execute($values);
        return ApiResponse::ok(['status' => 'ok']);
    }

    public function delete(Request $request, App $app, array $params): \App\Http\Response
    {
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error('VALIDATION_ERROR', 'Invalid id', 422);
        }
        $pdo = Database::pdo($app->config());
        $pdo->prepare('UPDATE restaurants SET deleted_at = NOW(), updated_at = NOW() WHERE id = ?')->execute([$id]);
        return ApiResponse::ok(['status' => 'ok']);
    }
}
