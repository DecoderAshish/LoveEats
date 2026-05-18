<?php
declare(strict_types=1);

namespace App\Controllers\AdminApi;

use App\Bootstrap\App;
use App\Http\Request;
use App\Support\ApiResponse;
use App\Support\Database;

final class BannersController extends BaseAdminApiController
{
    public function list(Request $request, App $app, array $params): \App\Http\Response
    {
        $pdo = Database::pdo($app->config());
        $st = $pdo->query('SELECT id, title, subtitle, image_path, link_url, placement, is_active, sort_order FROM banners ORDER BY sort_order ASC, id DESC LIMIT 100');
        $rows = $st ? $st->fetchAll() : [];
        return ApiResponse::ok(['items' => is_array($rows) ? $rows : []]);
    }

    public function create(Request $request, App $app, array $params): \App\Http\Response
    {
        $title = trim((string)($request->body['title'] ?? ''));
        $image = trim((string)($request->body['image_path'] ?? ''));
        $placement = trim((string)($request->body['placement'] ?? 'home'));
        if ($title === '' || $image === '') {
            return ApiResponse::error('VALIDATION_ERROR', 'title and image_path are required', 422);
        }
        $pdo = Database::pdo($app->config());
        $st = $pdo->prepare('INSERT INTO banners (title, subtitle, image_path, link_url, placement, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())');
        $st->execute([$title, $request->body['subtitle'] ?? null, $image, $request->body['link_url'] ?? null, $placement, (int)($request->body['sort_order'] ?? 0)]);
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
        foreach (['title', 'subtitle', 'image_path', 'link_url', 'placement'] as $f) {
            if (array_key_exists($f, $request->body)) {
                $fields[] = "{$f} = ?";
                $values[] = $request->body[$f] !== '' ? (string)$request->body[$f] : null;
            }
        }
        foreach (['sort_order', 'is_active'] as $f) {
            if (array_key_exists($f, $request->body)) {
                $fields[] = "{$f} = ?";
                $values[] = (int)$request->body[$f];
            }
        }
        if ($fields === []) {
            return ApiResponse::error('VALIDATION_ERROR', 'No fields to update', 422);
        }
        $values[] = $id;
        $pdo = Database::pdo($app->config());
        $sql = 'UPDATE banners SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = ?';
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
        $pdo->prepare('DELETE FROM banners WHERE id = ?')->execute([$id]);
        return ApiResponse::ok(['status' => 'ok']);
    }
}
