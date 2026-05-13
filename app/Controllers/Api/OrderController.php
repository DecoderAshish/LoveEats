<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Bootstrap\App;
use App\Http\Request;
use App\Support\ApiResponse;
use App\Support\Auth;
use App\Support\Database;
use App\Support\Pagination;
use App\Services\Cart\CartService;

final class OrderController extends BaseApiController
{
    public function list(Request $request, App $app, array $params): \App\Http\Response
    {
        $uid = Auth::userId();
        if ($uid === null) {
            return ApiResponse::error('UNAUTHENTICATED', 'Authentication required', 401);
        }
        $p = Pagination::fromQuery($request->query);
        $pdo = Database::pdo($app->config());
        $st = $pdo->prepare('SELECT id, order_public_id, restaurant_id, status, payment_status, total, created_at FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT ' . (int)$p['limit'] . ' OFFSET ' . (int)$p['offset']);
        $st->execute([$uid]);
        $rows = $st->fetchAll();
        return ApiResponse::ok(['items' => is_array($rows) ? $rows : [], 'pagination' => $p]);
    }

    public function details(Request $request, App $app, array $params): \App\Http\Response
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
        $o = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
        $o->execute([$id, $uid]);
        $order = $o->fetch();
        if (!is_array($order)) {
            return ApiResponse::error('NOT_FOUND', 'Order not found', 404);
        }
        $it = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC');
        $it->execute([$id]);
        $items = $it->fetchAll();
        $pay = $pdo->prepare('SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC');
        $pay->execute([$id]);
        $payments = $pay->fetchAll();
        return ApiResponse::ok(['order' => $order, 'items' => is_array($items) ? $items : [], 'payments' => is_array($payments) ? $payments : []]);
    }

    public function reorder(Request $request, App $app, array $params): \App\Http\Response
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
        $o = $pdo->prepare('SELECT id FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
        $o->execute([$id, $uid]);
        $orderId = $o->fetchColumn();
        if ($orderId === false) {
            return ApiResponse::error('NOT_FOUND', 'Order not found', 404);
        }
        $it = $pdo->prepare('SELECT food_item_id, variant_id, quantity, instructions, add_ons_snapshot FROM order_items WHERE order_id = ? ORDER BY id ASC');
        $it->execute([$id]);
        $items = $it->fetchAll();
        $items = is_array($items) ? $items : [];
        if ($items === []) {
            return ApiResponse::error('REORDER_FAILED', 'No items to reorder', 400);
        }

        $cartService = new CartService($pdo);
        $cartId = $cartService->ensureCart($uid);
        $cartService->clearCart($cartId);

        foreach ($items as $row) {
            $addonIds = [];
            $snapshot = $row['add_ons_snapshot'] ?? null;
            if ($snapshot !== null) {
                $decoded = json_decode((string)$snapshot, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $a) {
                        if (is_array($a) && isset($a['id'])) {
                            $addonIds[] = (int)$a['id'];
                        }
                    }
                }
            }
            $cartService->addItem(
                $uid,
                (int)$row['food_item_id'],
                $row['variant_id'] !== null ? (int)$row['variant_id'] : null,
                (int)$row['quantity'],
                $addonIds,
                $row['instructions'] !== null ? (string)$row['instructions'] : null
            );
        }

        return ApiResponse::ok(['status' => 'ok', 'cart' => $cartService->getCart($uid)]);
    }

    public function createSupportTicket(Request $request, App $app, array $params): \App\Http\Response
    {
        $uid = Auth::userId();
        if ($uid === null) {
            return ApiResponse::error('UNAUTHENTICATED', 'Authentication required', 401);
        }
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        $subject = trim((string)($request->body['subject'] ?? 'Help with order'));
        $message = trim((string)($request->body['message'] ?? ''));
        if ($id <= 0 || $message === '') {
            return ApiResponse::error('VALIDATION_ERROR', 'order id and message are required', 422);
        }
        $pdo = Database::pdo($app->config());
        $st = $pdo->prepare('INSERT INTO support_tickets (user_id, order_id, subject, message, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
        $st->execute([$uid, $id, $subject, $message, 'open']);
        return ApiResponse::ok(['ticket_id' => (int)$pdo->lastInsertId(), 'status' => 'open']);
    }
}
