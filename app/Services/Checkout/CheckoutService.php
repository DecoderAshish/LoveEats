<?php
declare(strict_types=1);

namespace App\Services\Checkout;

use App\Services\Cart\CartService;
use App\Support\Str;

final class CheckoutService
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string,mixed> */
    public function preview(int $userId, ?int $addressId = null): array
    {
        $cartService = new CartService($this->pdo);
        $cartData = $cartService->getCart($userId);
        $cart = $cartData['cart'];
        $items = $cartData['items'];
        $subtotal = (float)$cartData['subtotal'];

        $taxRate = $this->taxRateForUser($userId);
        $taxes = round($subtotal * $taxRate / 100.0, 2);
        $deliveryFee = $subtotal > 0 ? 29.00 : 0.00;

        $couponId = isset($cart['coupon_id']) ? (int)$cart['coupon_id'] : null;
        $coupon = null;
        $discount = 0.0;
        $freeDelivery = false;
        if ($couponId !== null && $couponId > 0) {
            $coupon = $this->couponById($couponId);
            if ($coupon !== null) {
                $calc = $this->calculateCouponDiscount($coupon, $subtotal);
                $discount = $calc['discount'];
                $freeDelivery = $calc['free_delivery'];
            }
        }
        if ($freeDelivery) {
            $deliveryFee = 0.00;
        }

        $tip = isset($cart['tip_amount']) ? (float)$cart['tip_amount'] : 0.0;
        $preWalletTotal = max(0.0, $subtotal + $taxes + $deliveryFee + $tip - $discount);

        $cashback = $this->cashbackForTotal($preWalletTotal);

        return [
            'cart' => $cart,
            'items' => $items,
            'pricing' => [
                'subtotal' => $subtotal,
                'tax_rate_percent' => $taxRate,
                'taxes' => $taxes,
                'delivery_fee' => $deliveryFee,
                'tip' => $tip,
                'discount' => round($discount, 2),
                'total' => round($preWalletTotal, 2),
                'cashback_estimate' => $cashback,
            ],
            'coupon' => $coupon,
        ];
    }

    /** @return array{order_id:int, order_public_id:string} */
    public function placeOrder(int $userId, int $addressId, string $paymentMethod, float $walletUse = 0.0): array
    {
        $cartService = new CartService($this->pdo);
        $cartData = $cartService->getCart($userId);
        $cart = $cartData['cart'];
        $items = $cartData['items'];
        if ($items === []) {
            throw new \RuntimeException('Cart is empty');
        }
        $restaurantId = isset($cart['restaurant_id']) ? (int)$cart['restaurant_id'] : 0;
        if ($restaurantId <= 0) {
            throw new \RuntimeException('Cart restaurant missing');
        }

        $preview = $this->preview($userId, $addressId);
        $total = (float)$preview['pricing']['total'];

        $wallet = $this->walletForUser($userId);
        $walletBalance = $wallet ? (float)$wallet['balance'] : 0.0;
        $walletUse = max(0.0, min($walletBalance, min($walletUse, $total)));
        $totalAfterWallet = round($total - $walletUse, 2);

        $orderPublicId = 'LEO' . strtoupper(substr(Str::random(12), 0, 10));
        $this->pdo->beginTransaction();
        try {
            $ins = $this->pdo->prepare('INSERT INTO orders (order_public_id, user_id, restaurant_id, address_id, fulfillment_type, status, payment_status, subtotal, taxes_total, delivery_fee, tip, discount_total, cashback_earned, wallet_used, total, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $ins->execute([
                $orderPublicId,
                $userId,
                $restaurantId,
                $addressId,
                'delivery',
                'confirmed',
                $paymentMethod === 'cod' ? 'pending' : 'initiated',
                (float)$preview['pricing']['subtotal'],
                (float)$preview['pricing']['taxes'],
                (float)$preview['pricing']['delivery_fee'],
                (float)$preview['pricing']['tip'],
                (float)$preview['pricing']['discount'],
                (float)$preview['pricing']['cashback_estimate'],
                $walletUse,
                $totalAfterWallet,
            ]);
            $orderId = (int)$this->pdo->lastInsertId();

            $oi = $this->pdo->prepare('INSERT INTO order_items (order_id, food_item_id, variant_id, name_snapshot, unit_price, quantity, add_ons_snapshot, instructions, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            foreach ($items as $it) {
                $addons = $it['add_ons'] ?? [];
                $addonsSnapshot = [];
                if (is_array($addons)) {
                    foreach ($addons as $a) {
                        $addonsSnapshot[] = [
                            'id' => (int)$a['add_on_id'],
                            'name' => (string)$a['name'],
                            'price' => (float)$a['price'],
                        ];
                    }
                }
                $oi->execute([
                    $orderId,
                    (int)$it['food_item_id'],
                    $it['variant_id'] !== null ? (int)$it['variant_id'] : null,
                    (string)$it['food_name'],
                    (float)$it['unit_total'],
                    (int)$it['quantity'],
                    json_encode($addonsSnapshot, JSON_UNESCAPED_SLASHES),
                    $it['instructions'] ?? null,
                ]);
            }

            $pay = $this->pdo->prepare('INSERT INTO payments (order_id, provider, method, amount, currency, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $pay->execute([$orderId, 'mock', $paymentMethod, $totalAfterWallet, 'INR', $paymentMethod === 'cod' ? 'pending' : 'initiated']);

            $dt = $this->pdo->prepare('INSERT INTO delivery_tracking (order_id, status, timeline) VALUES (?, ?, ?)');
            $timeline = json_encode([
                ['status' => 'confirmed', 'at' => gmdate('c')],
                ['status' => 'preparing', 'at' => gmdate('c')],
            ], JSON_UNESCAPED_SLASHES);
            $dt->execute([$orderId, 'preparing', $timeline]);

            if ($walletUse > 0.0 && $wallet !== null) {
                $this->pdo->prepare('UPDATE wallets SET balance = balance - ?, updated_at = NOW() WHERE id = ?')->execute([$walletUse, (int)$wallet['id']]);
                $this->pdo->prepare('INSERT INTO wallet_transactions (wallet_id, type, amount, reference_type, reference_id, description, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())')->execute([
                    (int)$wallet['id'],
                    'debit',
                    -1 * $walletUse,
                    'order',
                    $orderId,
                    'Wallet used for order ' . $orderPublicId,
                ]);
            }

            $cartService->clearCart((int)$cart['id']);

            $this->pdo->commit();
            return ['order_id' => $orderId, 'order_public_id' => $orderPublicId];
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function taxRateForUser(int $userId): float
    {
        $cityId = $this->userCityId($userId);
        $st = $this->pdo->prepare('SELECT rate_percent FROM taxes WHERE is_active = 1 AND (city_id IS NULL OR city_id = ?) ORDER BY city_id DESC, id DESC LIMIT 1');
        $st->execute([$cityId]);
        $rate = $st->fetchColumn();
        return $rate === false ? 0.0 : (float)$rate;
    }

    private function userCityId(int $userId): ?int
    {
        $st = $this->pdo->prepare('SELECT city_id FROM addresses WHERE user_id = ? AND deleted_at IS NULL ORDER BY is_default DESC, id DESC LIMIT 1');
        $st->execute([$userId]);
        $city = $st->fetchColumn();
        if ($city === false || $city === null) {
            return null;
        }
        return (int)$city;
    }

    private function cashbackForTotal(float $total): float
    {
        $st = $this->pdo->prepare("SELECT value_json FROM settings WHERE `key` = 'cashback' LIMIT 1");
        $st->execute();
        $raw = $st->fetchColumn();
        if ($raw === false || $raw === null) {
            return 0.0;
        }
        $cfg = json_decode((string)$raw, true);
        if (!is_array($cfg) || empty($cfg['enabled'])) {
            return 0.0;
        }
        $percent = (float)($cfg['percent'] ?? 0);
        $max = (float)($cfg['max'] ?? 0);
        $cashback = $total * $percent / 100.0;
        if ($max > 0) {
            $cashback = min($cashback, $max);
        }
        return round(max(0.0, $cashback), 2);
    }

    private function couponById(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT id, code, type, value, max_discount, min_order FROM coupons WHERE id = ? AND is_active = 1 AND deleted_at IS NULL AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return is_array($row) ? $row : null;
    }

    /** @return array{discount:float, free_delivery:bool} */
    private function calculateCouponDiscount(array $coupon, float $subtotal): array
    {
        if (($coupon['min_order'] ?? null) !== null && $subtotal < (float)$coupon['min_order']) {
            return ['discount' => 0.0, 'free_delivery' => false];
        }
        $type = (string)($coupon['type'] ?? '');
        if ($type === 'free_delivery') {
            return ['discount' => 0.0, 'free_delivery' => true];
        }
        if ($type === 'percent') {
            $discount = $subtotal * ((float)$coupon['value'] / 100.0);
            if (($coupon['max_discount'] ?? null) !== null) {
                $discount = min($discount, (float)$coupon['max_discount']);
            }
            return ['discount' => round(max(0.0, $discount), 2), 'free_delivery' => false];
        }
        if ($type === 'flat') {
            return ['discount' => round(max(0.0, min($subtotal, (float)$coupon['value'])), 2), 'free_delivery' => false];
        }
        return ['discount' => 0.0, 'free_delivery' => false];
    }

    private function walletForUser(int $userId): ?array
    {
        $st = $this->pdo->prepare('SELECT id, balance FROM wallets WHERE user_id = ? LIMIT 1');
        $st->execute([$userId]);
        $row = $st->fetch();
        return is_array($row) ? $row : null;
    }
}

