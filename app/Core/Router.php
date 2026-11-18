<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private static array $routes = [];

    public static function get(string $path, callable|array $action): void
    {
        self::add('GET', $path, $action);
    }

    public static function post(string $path, callable|array $action): void
    {
        self::add('POST', $path, $action);
    }

    private static function add(string $method, string $path, callable|array $action): void
    {
        self::$routes[$method][$path] = $action;
    }

    public static function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $uri = '/' . trim($uri, '/');
        if ($uri === '//') {
            $uri = '/';
        }
        if ($method === 'POST') {
            Csrf::verify();
        }

        foreach (self::$routes[$method] ?? [] as $path => $action) {
            $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);
            $pattern = '#^' . $pattern . '/?$#';
            if (!preg_match($pattern, $uri, $matches)) {
                continue;
            }
            $parameters = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $parameters[$key] = rawurldecode($value);
                }
            }
            if (is_array($action)) {
                [$class, $function] = $action;
                (new $class())->{$function}(...$parameters);
                return;
            }
            $action(...$parameters);
            return;
        }

        http_response_code(404);
        View::render('errors/404', ['title' => t('Page not found')]);
    }
}
