<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Bootstrap\App;
use App\Http\Request;
use App\Support\ApiResponse;
use App\Support\Auth;
use App\Support\Database;

final class NotificationController extends BaseApiController
{
    public function list(Request $request, App $app, array $params): \App\Http\Response
    {
        $uid = Auth::userId();
        if ($uid === null) {
            return ApiResponse::error('UNAUTHENTICATED', 'Authentication required', 401);
        }
        $pdo = Database::pdo($app->config());
        $st = $pdo->prepare('SELECT id, type, title, body, data, is_read, created_at, read_at FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 50');
        $st->execute([$uid]);
        $rows = $st->fetchAll();
        return ApiResponse::ok(['items' => is_array($rows) ? $rows : []]);
    }

    public function markRead(Request $request, App $app, array $params): \App\Http\Response
    {
        $uid = Auth::userId();
        if ($uid === null) {
            return ApiResponse::error('UNAUTHENTICATED', 'Authentication required', 401);
        }
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        if ($id <= 0) {
            return ApiResponse::error('VALIDATION_ERROR', 'Invalid id', 422);
        }
        $pdo = Database::pdo($app->config());
        $pdo->prepare('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?')->execute([$id, $uid]);
        return ApiResponse::ok(['status' => 'ok']);
    }
}
