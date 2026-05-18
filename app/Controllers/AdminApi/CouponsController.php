<?php
declare(strict_types=1);

namespace App\Controllers\AdminApi;

use App\Bootstrap\App;
use App\Http\Request;
use App\Support\ApiResponse;
use App\Support\Database;
use App\Support\Pagination;

final class CouponsController extends BaseAdminApiController
{
    public function list(Request $request, App $app, array $params): \App\Http\Response
    {
        $p = Pagination::fromQuery($request->query);
        $pdo = Database::pdo($app->config());
        $st = $pdo->prepare('SELECT id, code, title, type, value, is_active, starts_at, ends_at FROM coupons WHERE deleted_at IS NULL ORDER BY id DESC LIMIT ' . (int)$p['limit'] . ' OFFSET ' . (int)$p['offset']);
        $st->execute();
        $rows = $st->fetchAll();
        return ApiResponse::ok(['items' => is_array($rows) ? $rows : [], 'pagination' => $p]);
    }

    public function create(Request $request, App $app, array $params): \App\Http\Response
    {
        $code = strtoupper(trim((string)($request->body['code'] ?? '')));
        $title = trim((string)($request->body['title'] ?? ''));
        $type = trim((string)($request->body['type'] ?? 'flat'));
        $value = isset($request->body['value']) ? (float)$request->body['value'] : 0.0;
        if ($code === '' || $title === '' || $type === '') {
            return ApiResponse::error('VALIDATION_ERROR', 'code, title, type are required', 422);
        }
        $pdo = Database::pdo($app->config());
        $st = $pdo->prepare('INSERT INTO coupons (code, title, type, value, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())');
        $st->execute([$code, $title, $type, $value]);
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
        foreach (['title', 'description', 'type'] as $f) {
            if (array_key_exists($f, $request->body)) {
                $fields[] = "{$f} = ?";
                $values[] = $request->body[$f] !== '' ? (string)$request->body[$f] : null;
            }
        }
        foreach (['value', 'max_discount', 'min_order'] as $f) {
            if (array_key_exists($f, $request->body)) {
                $fields[] = "{$f} = ?";
                $values[] = $request->body[$f] !== null ? (float)$request->body[$f] : null;
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
        $sql = 'UPDATE coupons SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = ? AND deleted_at IS NULL';
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
        $pdo->prepare('UPDATE coupons SET deleted_at = NOW(), updated_at = NOW() WHERE id = ?')->execute([$id]);
        return ApiResponse::ok(['status' => 'ok']);
    }
}
