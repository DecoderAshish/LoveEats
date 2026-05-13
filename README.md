# LoveEats
Food Delivery Customer Web App (LOVE EATS) built with PHP 8+ + MySQL + PDO using a lightweight MVC-ish architecture (SSR pages + REST APIs).

## 1) Requirements
- PHP 8.2+
- MySQL 8.0+

## 2) Setup
1. Copy env file

```bash
cp .env.example .env
```

2. Update database credentials inside `.env`

3. Run migrations

```bash
php scripts/migrate.php
```

4. Seed demo data

```bash
php scripts/seed.php
```

5. Run locally

```bash
php -S 0.0.0.0:8000 -t public
```

Open:
- http://localhost:8000/ (Landing)
- http://localhost:8000/login (Login)
- http://localhost:8000/restaurants (Restaurant listing)

## 3) Demo Credentials
- Customer: demo@loveeats.local / Demo@12345
- Admin: admin@loveeats.local / Admin@12345

## 4) Key API Endpoints
### Customer
- POST /api/v1/auth/login
- POST /api/v1/auth/otp/request
- POST /api/v1/auth/otp/verify
- GET /api/v1/restaurants
- GET /api/v1/restaurants/slug/{slug}
- GET /api/v1/reels
- GET /api/v1/cart (requires Bearer token)
- POST /api/v1/cart/items
- POST /api/v1/checkout/preview
- POST /api/v1/checkout/place-order

### Admin
- POST /admin-api/v1/auth/login
- GET /admin-api/v1/dashboard

### Vendor
- POST /vendor-api/v1/auth/login
- GET /vendor-api/v1/orders?restaurant_id=...

### Delivery Partner
- POST /dp-api/v1/auth/otp/request
- POST /dp-api/v1/auth/otp/verify
- POST /dp-api/v1/location

## 5) Project Structure
- public/: entrypoint + assets + SEO files
- app/: controllers, middleware, services, repositories, views
- routes/: web + API route definitions
- database/migrations/: SQL migrations
- database/seeders/: demo seeders
- scripts/: migrate/seed runners
- public/uploads/: public user uploads (avatars, demo reels placeholders)
