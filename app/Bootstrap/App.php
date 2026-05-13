<?php
declare(strict_types=1);

namespace App\Bootstrap;

use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Support\Config;
use App\Support\Logger;

final class App
{
    private string $rootPath;
    private Config $config;
    private Logger $logger;
    private Router $router;

    public static function create(string $rootPath): self
    {
        self::registerAutoload($rootPath);

        return new self($rootPath);
    }

    private function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, '/');
        $this->config = new Config($this->rootPath . '/app/Config');
        $this->logger = new Logger($this->rootPath . '/storage/logs/app.log');

        $this->router = new Router();
        $this->registerRoutes();
    }

    public function handle(): Response
    {
        $request = Request::fromGlobals();
        try {
            return $this->router->dispatch($request, $this);
        } catch (\Throwable $e) {
            $this->logger->error('Unhandled exception', [
                'message' => $e->getMessage(),
                'type' => $e::class,
            ]);
            return Response::html('Something went wrong.', 500);
        }
    }

    public function rootPath(): string
    {
        return $this->rootPath;
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function logger(): Logger
    {
        return $this->logger;
    }

    private function registerRoutes(): void
    {
        $webRoutes = $this->rootPath . '/routes/web.php';
        $apiRoutes = $this->rootPath . '/routes/api_v1.php';
        $adminApiRoutes = $this->rootPath . '/routes/admin_api_v1.php';
        $vendorApiRoutes = $this->rootPath . '/routes/vendor_api_v1.php';
        $deliveryApiRoutes = $this->rootPath . '/routes/delivery_api_v1.php';

        if (is_file($webRoutes)) {
            (static function (Router $router): void {
                require $GLOBALS['__routes_web__'];
            })($this->router);
        }

        if (is_file($apiRoutes)) {
            (static function (Router $router): void {
                require $GLOBALS['__routes_api__'];
            })($this->router);
        }

        if (is_file($adminApiRoutes)) {
            (static function (Router $router): void {
                require $GLOBALS['__routes_admin_api__'];
            })($this->router);
        }

        if (is_file($vendorApiRoutes)) {
            (static function (Router $router): void {
                require $GLOBALS['__routes_vendor_api__'];
            })($this->router);
        }

        if (is_file($deliveryApiRoutes)) {
            (static function (Router $router): void {
                require $GLOBALS['__routes_delivery_api__'];
            })($this->router);
        }
    }

    private static function registerAutoload(string $rootPath): void
    {
        spl_autoload_register(static function (string $class) use ($rootPath): void {
            $prefix = 'App\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }
            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file = rtrim($rootPath, '/') . '/app/' . $relative . '.php';
            if (is_file($file)) {
                require $file;
            }
        });

        $GLOBALS['__routes_web__'] = rtrim($rootPath, '/') . '/routes/web.php';
        $GLOBALS['__routes_api__'] = rtrim($rootPath, '/') . '/routes/api_v1.php';
        $GLOBALS['__routes_admin_api__'] = rtrim($rootPath, '/') . '/routes/admin_api_v1.php';
        $GLOBALS['__routes_vendor_api__'] = rtrim($rootPath, '/') . '/routes/vendor_api_v1.php';
        $GLOBALS['__routes_delivery_api__'] = rtrim($rootPath, '/') . '/routes/delivery_api_v1.php';
    }
}
