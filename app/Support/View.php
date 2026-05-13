<?php
declare(strict_types=1);

namespace App\Support;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $viewPath, array $data, string $basePath): string
    {
        $file = rtrim($basePath, '/') . '/app/Views/' . ltrim($viewPath, '/') . '.php';
        if (!is_file($file)) {
            return 'View not found.';
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        $content = ob_get_clean();
        return $content === false ? '' : $content;
    }
}
