<?php
declare(strict_types=1);

namespace App\Controllers\VendorApi;

use App\Bootstrap\App;
use App\Http\Request;
use App\Repositories\OtpRepository;
use App\Repositories\SessionRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use App\Services\Auth\AuthService;
use App\Services\Auth\JwtService;
use App\Support\ApiResponse;
use App\Support\Database;

final class VendorAuthController
{
    private function service(App $app): AuthService
    {
        $pdo = Database::pdo($app->config());
        return new AuthService(
            $pdo,
            new JwtService((string)$app->config()->get('app.jwt_secret')),
            new UserRepository($pdo),
            new SessionRepository($pdo),
            new OtpRepository($pdo),
            new WalletRepository($pdo),
        );
    }

    public function login(Request $request, App $app, array $params): \App\Http\Response
    {
        $email = trim((string)($request->body['email'] ?? ''));
        $password = (string)($request->body['password'] ?? '');
        if ($email === '' || $password === '') {
            return ApiResponse::error('VALIDATION_ERROR', 'email and password are required', 422);
        }
        try {
            $result = $this->service($app)->loginEmail($email, $password);
            $uid = (int)($result['user']['id'] ?? 0);
            $pdo = Database::pdo($app->config());
            $users = new UserRepository($pdo);
            $roles = $users->rolesForUser($uid);
            if (!in_array('vendor', $roles, true)) {
                return ApiResponse::error('FORBIDDEN', 'Vendor role required', 403);
            }
            return ApiResponse::ok($result);
        } catch (\Throwable $e) {
            return ApiResponse::error('LOGIN_FAILED', $e->getMessage(), 401);
        }
    }
}

