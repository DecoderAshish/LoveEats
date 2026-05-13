<?php
declare(strict_types=1);

use App\Support\Str;

return static function (PDO $pdo): void {
    $pdo->exec("SET time_zone = '+00:00'");

    $cityStmt = $pdo->prepare('INSERT INTO cities (name, slug, is_active) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE name=VALUES(name), is_active=VALUES(is_active)');
    foreach ([
        ['Mumbai', 'mumbai'],
        ['Bengaluru', 'bengaluru'],
        ['Delhi', 'delhi'],
    ] as $c) {
        $cityStmt->execute($c);
    }

    $getCityId = $pdo->prepare('SELECT id FROM cities WHERE slug = ?');
    $getCityId->execute(['mumbai']);
    $mumbaiId = (int)$getCityId->fetchColumn();

    $roleStmt = $pdo->prepare('INSERT INTO roles (name) VALUES (?) ON DUPLICATE KEY UPDATE name=VALUES(name)');
    foreach (['customer', 'admin', 'vendor', 'delivery_partner'] as $r) {
        $roleStmt->execute([$r]);
    }

    $permStmt = $pdo->prepare('INSERT INTO permissions (name) VALUES (?) ON DUPLICATE KEY UPDATE name=VALUES(name)');
    foreach ([
        'admin.dashboard.view',
        'admin.restaurants.manage',
        'admin.users.manage',
        'admin.orders.manage',
        'admin.coupons.manage',
        'admin.banners.manage',
        'admin.settings.manage',
    ] as $p) {
        $permStmt->execute([$p]);
    }

    $roleId = static function (PDO $pdo, string $name): int {
        $st = $pdo->prepare('SELECT id FROM roles WHERE name = ?');
        $st->execute([$name]);
        return (int)$st->fetchColumn();
    };

    $permId = static function (PDO $pdo, string $name): int {
        $st = $pdo->prepare('SELECT id FROM permissions WHERE name = ?');
        $st->execute([$name]);
        return (int)$st->fetchColumn();
    };

    $adminRoleId = $roleId($pdo, 'admin');
    $customerRoleId = $roleId($pdo, 'customer');

    $rpStmt = $pdo->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
    foreach ([
        'admin.dashboard.view',
        'admin.restaurants.manage',
        'admin.users.manage',
        'admin.orders.manage',
        'admin.coupons.manage',
        'admin.banners.manage',
        'admin.settings.manage',
    ] as $p) {
        $rpStmt->execute([$adminRoleId, $permId($pdo, $p)]);
    }

    $adminReferral = 'LE' . strtoupper(substr(hash('sha256', 'admin@loveeats'), 0, 10));
    $adminPass = password_hash('Admin@12345', PASSWORD_DEFAULT);
    $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, referral_code, email_verified_at, status) VALUES (?, ?, ?, ?, ?, NOW(), ?) ON DUPLICATE KEY UPDATE name=VALUES(name), password_hash=VALUES(password_hash), status=VALUES(status)')
        ->execute(['Love Eats Admin', 'admin@loveeats.local', '9000000000', $adminPass, $adminReferral, 'active']);
    $adminUserId = (int)$pdo->query("SELECT id FROM users WHERE email='admin@loveeats.local'")->fetchColumn();
    $pdo->prepare('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$adminUserId, $adminRoleId]);
    $pdo->prepare('INSERT IGNORE INTO wallets (user_id, balance) VALUES (?, ?)')->execute([$adminUserId, 0]);

    $customerReferral = 'LE' . strtoupper(substr(hash('sha256', 'demo@loveeats'), 0, 10));
    $customerPass = password_hash('Demo@12345', PASSWORD_DEFAULT);
    $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, referral_code, email_verified_at, status) VALUES (?, ?, ?, ?, ?, NOW(), ?) ON DUPLICATE KEY UPDATE name=VALUES(name), password_hash=VALUES(password_hash), status=VALUES(status)')
        ->execute(['Aarav Mehta', 'demo@loveeats.local', '9111111111', $customerPass, $customerReferral, 'active']);
    $customerUserId = (int)$pdo->query("SELECT id FROM users WHERE email='demo@loveeats.local'")->fetchColumn();
    $pdo->prepare('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$customerUserId, $customerRoleId]);
    $pdo->prepare('INSERT IGNORE INTO wallets (user_id, balance) VALUES (?, ?)')->execute([$customerUserId, 250.00]);

    $pdo->prepare('INSERT INTO wallet_transactions (wallet_id, type, amount, reference_type, reference_id, description) SELECT w.id, ?, ?, ?, ?, ? FROM wallets w WHERE w.user_id = ?')
        ->execute(['credit', 250.00, 'seed', null, 'Welcome balance', $customerUserId]);

    $pdo->prepare('INSERT INTO addresses (user_id, label, address_line1, address_line2, city_id, postal_code, instructions, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE address_line1=VALUES(address_line1), is_default=VALUES(is_default)')
        ->execute([$customerUserId, 'Home', '221B Warm Street', 'Near Yellow Mall', $mumbaiId, '400001', 'Call on arrival']);

    $restaurants = [
        [
            'Sunrise Kitchen',
            'sunrise-kitchen',
            'platform',
            'Warm bowls, bold wraps, and dessert drops.',
            json_encode(['Bowls', 'Wraps', 'Desserts']),
        ],
        [
            'Noir Noodles',
            'noir-noodles',
            'partner',
            'Minimal, premium noodles with unapologetic spice.',
            json_encode(['Asian', 'Noodles', 'Spicy']),
        ],
        [
            'Golden Tandoor',
            'golden-tandoor',
            'partner',
            'Charred comfort with a bright yellow soul.',
            json_encode(['North Indian', 'Tandoor']),
        ],
    ];

    $restStmt = $pdo->prepare('INSERT INTO restaurants (city_id, name, slug, type, description, cuisines, rating, rating_count, price_level, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), cuisines=VALUES(cuisines), is_active=VALUES(is_active)');
    foreach ($restaurants as $r) {
        $restStmt->execute([$mumbaiId, $r[0], $r[1], $r[2], $r[3], $r[4], 4.6, 1200, 2]);
    }

    $getRestId = $pdo->prepare('SELECT id FROM restaurants WHERE slug = ?');
    $getRestId->execute(['sunrise-kitchen']);
    $sunriseId = (int)$getRestId->fetchColumn();

    $pdo->prepare('INSERT INTO restaurant_branches (restaurant_id, city_id, name, address_text, lat, lng, phone, open_time, close_time, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE address_text=VALUES(address_text), is_active=VALUES(is_active)')
        ->execute([$sunriseId, $mumbaiId, 'Sunrise Kitchen — Bandra', 'Bandra West, Mumbai', 19.0600, 72.8300, '9222222222', '10:00', '02:00']);
    $sunriseBranchId = (int)$pdo->query("SELECT id FROM restaurant_branches WHERE restaurant_id={$sunriseId} ORDER BY id ASC LIMIT 1")->fetchColumn();

    $catStmt = $pdo->prepare('INSERT INTO food_categories (name, slug, sort_order, is_active) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE name=VALUES(name), sort_order=VALUES(sort_order), is_active=VALUES(is_active)');
    foreach ([
        ['Wraps', 'wraps', 10],
        ['Bowls', 'bowls', 20],
        ['Desserts', 'desserts', 30],
        ['Beverages', 'beverages', 40],
    ] as $c) {
        $catStmt->execute($c);
    }

    $catId = static function (PDO $pdo, string $slug): int {
        $st = $pdo->prepare('SELECT id FROM food_categories WHERE slug = ?');
        $st->execute([$slug]);
        return (int)$st->fetchColumn();
    };

    $items = [
        ['Spicy Paneer Wrap', 'spicy-paneer-wrap', 'wraps', 199.00, 1, 2, 420, json_encode(['veg','high-protein'])],
        ['Cheesy Volcano Fries', 'cheesy-volcano-fries', 'bowls', 179.00, 1, 1, 540, json_encode(['veg'])],
        ['Mango Lassi', 'mango-lassi', 'beverages', 99.00, 1, 0, 210, json_encode(['veg'])],
        ['Midnight Choco Lava', 'midnight-choco-lava', 'desserts', 149.00, 1, 0, 380, json_encode(['veg'])],
    ];

    $itemStmt = $pdo->prepare('INSERT INTO food_items (restaurant_id, food_category_id, name, slug, description, is_veg, spicy_level, calories, dietary_tags, base_price, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), base_price=VALUES(base_price), is_active=VALUES(is_active)');
    foreach ($items as $it) {
        $itemStmt->execute([
            $sunriseId,
            $catId($pdo, $it[2]),
            $it[0],
            $it[1],
            'Signature ' . $it[0] . ' crafted for fast cravings.',
            $it[4],
            $it[5],
            $it[6],
            $it[7],
            $it[3],
        ]);
    }

    $getItemId = $pdo->prepare('SELECT id FROM food_items WHERE restaurant_id = ? AND slug = ?');
    $getItemId->execute([$sunriseId, 'spicy-paneer-wrap']);
    $wrapId = (int)$getItemId->fetchColumn();

    $variantStmt = $pdo->prepare('INSERT INTO food_variants (food_item_id, name, price, is_default) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE price=VALUES(price), is_default=VALUES(is_default)');
    foreach ([
        [$wrapId, 'Regular', 199.00, 1],
        [$wrapId, 'Large', 239.00, 0],
    ] as $v) {
        $variantStmt->execute($v);
    }

    $addonStmt = $pdo->prepare('INSERT INTO add_ons (restaurant_id, name, price, is_active) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE price=VALUES(price), is_active=VALUES(is_active)');
    foreach ([
        [$sunriseId, 'Extra cheese', 39.00],
        [$sunriseId, 'Spice shot', 19.00],
        [$sunriseId, 'Garlic dip', 25.00],
    ] as $a) {
        $addonStmt->execute($a);
    }

    $addonId = static function (PDO $pdo, int $restaurantId, string $name): int {
        $st = $pdo->prepare('SELECT id FROM add_ons WHERE restaurant_id = ? AND name = ?');
        $st->execute([$restaurantId, $name]);
        return (int)$st->fetchColumn();
    };

    $fioStmt = $pdo->prepare('INSERT IGNORE INTO food_item_add_ons (food_item_id, add_on_id) VALUES (?, ?)');
    foreach (['Extra cheese', 'Spice shot', 'Garlic dip'] as $aName) {
        $fioStmt->execute([$wrapId, $addonId($pdo, $sunriseId, $aName)]);
    }

    $couponStmt = $pdo->prepare('INSERT INTO coupons (code, title, description, type, value, max_discount, min_order, restaurant_id, city_id, starts_at, ends_at, usage_limit_total, usage_limit_per_user, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), ?, ?, 1) ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), type=VALUES(type), value=VALUES(value), is_active=VALUES(is_active)');
    $couponStmt->execute(['LOVE50', '₹50 off', 'Flat ₹50 off on orders above ₹299.', 'flat', 50.00, 50.00, 299.00, null, $mumbaiId, 10000, 2]);
    $couponStmt->execute(['FIRST20', '20% first order', '20% off for your first order.', 'percent', 20.00, 120.00, 199.00, null, $mumbaiId, 5000, 1]);
    $couponStmt->execute(['FREED', 'Free delivery', 'Delivery fee waived above ₹249.', 'free_delivery', 0.00, null, 249.00, null, $mumbaiId, 8000, 2]);

    $reelStmt = $pdo->prepare('INSERT INTO reels (restaurant_id, food_item_id, video_path, poster_path, caption, likes_count, is_active) VALUES (?, ?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE caption=VALUES(caption), is_active=VALUES(is_active)');
    $reelStmt->execute([$sunriseId, $wrapId, '/storage/uploads/demo/reel1.mp4', '/storage/uploads/demo/reel1.jpg', 'The wrap that snaps back.', 1200]);
    $reelStmt->execute([$sunriseId, $wrapId, '/storage/uploads/demo/reel2.mp4', '/storage/uploads/demo/reel2.jpg', 'Cheese pull, no apologies.', 980]);

    $bannerStmt = $pdo->prepare('INSERT INTO banners (city_id, title, subtitle, image_path, link_url, placement, starts_at, ends_at, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), ?, 1) ON DUPLICATE KEY UPDATE subtitle=VALUES(subtitle), is_active=VALUES(is_active)');
    $bannerStmt->execute([$mumbaiId, 'Midnight cravings', 'Limited drops till 2 AM', '/storage/uploads/demo/banner_midnight.jpg', '/midnight', 'home', 10]);
    $bannerStmt->execute([$mumbaiId, 'Couple meals', 'For two, for tonight', '/storage/uploads/demo/banner_couple.jpg', '/couples', 'landing', 20]);

    $dpStmt = $pdo->prepare('INSERT INTO delivery_partners (name, phone, email, status, is_available, last_lat, last_lng, last_seen_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name), status=VALUES(status), is_available=VALUES(is_available)');
    $dpStmt->execute(['Priya Sharma', '9333333333', 'priya@partners.local', 'active', 1, 19.0605, 72.8305]);
    $dpStmt->execute(['Kabir Singh', '9444444444', 'kabir@partners.local', 'active', 0, 19.0500, 72.8200]);

    $taxStmt = $pdo->prepare('INSERT INTO taxes (city_id, name, rate_percent, is_active) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE rate_percent=VALUES(rate_percent), is_active=VALUES(is_active)');
    $taxStmt->execute([$mumbaiId, 'GST', 5.00]);

    $commStmt = $pdo->prepare('INSERT INTO commissions (restaurant_id, type, value, is_active) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE type=VALUES(type), value=VALUES(value), is_active=VALUES(is_active)');
    $commStmt->execute([$sunriseId, 'percent', 18.00]);

    $setStmt = $pdo->prepare('INSERT INTO settings (`key`, value_json) VALUES (?, ?) ON DUPLICATE KEY UPDATE value_json=VALUES(value_json)');
    $setStmt->execute(['currency', json_encode(['code' => 'INR'])]);
    $setStmt->execute(['cashback', json_encode(['enabled' => true, 'percent' => 4.0, 'max' => 25.0])]);
    $setStmt->execute(['referral', json_encode(['enabled' => true, 'bonus_referrer' => 50.0, 'bonus_referred' => 30.0, 'first_order_min' => 199.0])]);

    $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, body, data, is_read) VALUES (?, ?, ?, ?, ?, 0)');
    $notifStmt->execute([$customerUserId, 'promo', 'Welcome to Love Eats', 'Your wallet has ₹250 welcome balance.', json_encode(['source' => 'seed'])]);
};
