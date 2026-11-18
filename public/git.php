<?php

declare(strict_types=1);

use App\Services\GitHttpService;

require dirname(__DIR__) . '/app/bootstrap.php';

ini_set('zlib.output_compression', 'Off');
ini_set('output_buffering', 'Off');

try {
    (new GitHttpService())->handle();
} catch (Throwable $exception) {
    error_log((string) $exception);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo "Git service unavailable.\n";
}
