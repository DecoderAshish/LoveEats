# Deployment Guide

## 1) Environment
- PHP-FPM 8.2+
- Nginx (recommended) or Apache
- MySQL 8.0+

## 2) Configure Environment Variables
Create a `.env` on the server:
- `APP_ENV=production`
- `APP_BASE_URL=https://your-domain.tld`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `JWT_SECRET` (strong random string)
- `APP_KEY` (strong random string)

## 3) Nginx Example
Point the document root to `public/` and forward PHP requests to PHP-FPM.

```
server {
  listen 80;
  server_name your-domain.tld;

  root /var/www/love-eats/public;
  index index.php;

  location / {
    try_files $uri $uri/ /index.php?$query_string;
  }

  location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
  }
}
```

## 4) Migrations & Seeders
After code deploy and env setup:

```bash
php scripts/migrate.php
php scripts/seed.php
```

Seeders are safe to re-run for demo environments, but for production you typically run only migrations and remove demo seeders.

## 5) Storage Permissions
Ensure these are writable by the PHP-FPM user:
- `storage/logs`
- `storage/cache`
- `public/uploads`

## 6) HTTPS
Enable HTTPS via Let’s Encrypt (recommended). Cookies are configured for secure mode when HTTPS is detected.

## 7) Scaling Notes
- Put `storage/cache` behind Redis for multi-instance deployments (cache interface is middleware-ready).
- Serve `/public/assets` and `/public/uploads` via CDN.
- Add read replicas for MySQL when analytics load grows.
