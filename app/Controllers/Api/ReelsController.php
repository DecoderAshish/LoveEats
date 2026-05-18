<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Bootstrap\App;
use App\Http\Request;
use App\Support\ApiResponse;
use App\Support\Database;

final class ReelsController extends BaseApiController
{
    public function feed(Request $request, App $app, array $params): \App\Http\Response
    {
        $pdo = Database::pdo($app->config());
        $st = $pdo->query('SELECT r.id, r.caption, r.video_path, r.poster_path, r.likes_count, r.created_at, res.id AS restaurant_id, res.name AS restaurant_name, fi.id AS food_item_id, fi.name AS food_item_name, fi.base_price FROM reels r INNER JOIN restaurants res ON res.id = r.restaurant_id LEFT JOIN food_items fi ON fi.id = r.food_item_id WHERE r.is_active = 1 ORDER BY r.id DESC LIMIT 30');
        $rows = $st ? $st->fetchAll() : [];
        return ApiResponse::ok(['items' => is_array($rows) ? $rows : []]);
    }
}
