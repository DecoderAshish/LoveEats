<?php
declare(strict_types=1);

return [
    'env' => getenv('APP_ENV') ?: 'local',
    'name' => getenv('APP_NAME') ?: 'Love Eats',
    'base_url' => getenv('APP_BASE_URL') ?: 'http://localhost:8000',
    'jwt_secret' => getenv('JWT_SECRET') ?: 'change_me',
    'app_key' => getenv('APP_KEY') ?: 'base64:change_me',
    'upload_max_bytes' => (int)(getenv('UPLOAD_MAX_BYTES') ?: 5242880),
];
