<?php
declare(strict_types=1);

namespace App\Controllers\AdminApi;

use App\Bootstrap\App;
use App\Http\Request;
use App\Support\ApiResponse;
use App\Support\Database;
use App\Support\Pagination;

final class UsersController extends BaseAdminApiController
{
    public function list(Request $request, App $app, array $params): \App\Http\Response
    {
        $p = Pagination::fromQuery($request->query);
        $pdo = Database::pdo($app->config());
        $st = $pdo->prepare('SELECT id, name, email, phone, status, created_at FROM users WHERE deleted_at IS NULL ORDER BY id DESC LIMIT ' . (int)$p['limit'] . ' OFFSET ' . (int)$p['offset']);
        $st->execute();
        $rows = $st->fetchAll();
        return ApiResponse::ok(['items' => is_array($rows) ? $rows : [], 'pagination' => $p]);
    }

    public function update(Request $request, App $app, array $params): \App\Http\Response
    {
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error('VALIDATION_ERROR', 'Invalid id', 422);
        }
        $status = array_key_exists('status', $request->body) ? trim((string)$request->body['status']) : null;
        if ($status === null || $status === '') {
            return ApiResponse::error('VALIDATION_ERROR', 'status is required', 422);
        }
        $pdo = Database::pdo($app->config());
        $pdo->prepare('UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$status, $id]);
        return ApiResponse::ok(['status' => 'ok']);
    }
}
