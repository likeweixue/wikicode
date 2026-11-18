<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $view, array $data = [], string $layout = 'layout'): void
    {
        $viewFile = dirname(__DIR__) . '/views/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException("View not found: {$view}");
        }
        $data = array_merge(self::$shared, $data);
        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === '') {
            echo $content;
            return;
        }
        $layoutFile = dirname(__DIR__) . '/views/layouts/' . $layout . '.php';
        require $layoutFile;
    }
}
