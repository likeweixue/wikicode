<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;
use Throwable;

final class GitHttpService
{
    public function handle(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (!preg_match('#^/([^/]+)/([^/]+)\.git((?:/.*)?)$#', $uri, $matches)) {
            $this->fail(404, 'Repository endpoint not found.');
            return;
        }

        $owner = rawurldecode($matches[1]);
        $name = rawurldecode($matches[2]);
        $suffix = $matches[3] ?: '';
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $service = null;

        if ($method === 'GET' && $suffix === '/info/refs') {
            $requested = $_GET['service'] ?? '';
            if (in_array($requested, ['git-upload-pack', 'git-receive-pack'], true)) {
                $service = $requested;
            }
        } elseif ($method === 'POST' && in_array($suffix, ['/git-upload-pack', '/git-receive-pack'], true)) {
            $service = ltrim($suffix, '/');
        }

        if ($service === null || str_contains($owner, "\0") || str_contains($name, "\0")) {
            $this->fail(404, 'Unsupported Git service.');
            return;
        }

        $repository = RepositoryService::find($owner, $name);
        if (!$repository) {
            $this->fail(404, 'Repository not found.');
            return;
        }

        $write = $service === 'git-receive-pack';
        $user = null;
        if ($repository['visibility'] === 'private' || $write) {
            $user = $this->authenticate();
            if (!$user) {
                header('WWW-Authenticate: Basic realm="wikicode Git", charset="UTF-8"');
                $this->fail(401, 'Authentication required.');
                return;
            }
            $allowed = $user['role'] === 'admin' || (int) $user['id'] === (int) $repository['owner_id'];
            if (!$allowed) {
                $this->fail(403, 'Repository permission denied.');
                return;
            }
        }

        $path = RepositoryService::path((int) $repository['id']);
        if (!is_dir($path)) {
            $this->fail(404, 'Repository storage is unavailable.');
            return;
        }

        try {
            $this->proxy($repository, $path, $suffix, $service, $method, $user);
        } catch (Throwable $exception) {
            error_log('Git HTTP error: ' . $exception->getMessage());
            if (!headers_sent()) {
                $this->fail(500, 'Git service temporarily unavailable.');
            }
        }
    }

    private function authenticate(): ?array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if ((!is_string($header) || !str_starts_with($header, 'Basic ')) && isset($_SERVER['PHP_AUTH_USER'])) {
            $header = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . ($_SERVER['PHP_AUTH_PW'] ?? ''));
        }
        if (!is_string($header) || !str_starts_with($header, 'Basic ')) {
            return null;
        }
        $decoded = base64_decode(substr($header, 6), true);
        if ($decoded === false || !str_contains($decoded, ':')) {
            return null;
        }
        [$login, $password] = explode(':', $decoded, 2);
        $user = Database::fetch(
            'SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1',
            [$login, $login]
        );
        return $user && password_verify($password, $user['password']) ? $user : null;
    }

    private function proxy(
        array $repository,
        string $path,
        string $suffix,
        string $service,
        string $method,
        ?array $user
    ): void {
        $tmp = dirname($path, 2) . '/tmp';
        if (!is_dir($tmp) && !mkdir($tmp, 0775, true) && !is_dir($tmp)) {
            throw new RuntimeException('Unable to create Git temporary directory.');
        }
        $bodyPath = tempnam($tmp, 'git-body-');
        $outputPath = tempnam($tmp, 'git-output-');
        $errorPath = tempnam($tmp, 'git-error-');
        if ($bodyPath === false || $outputPath === false || $errorPath === false) {
            throw new RuntimeException('Unable to allocate Git temporary files.');
        }

        $process = null;
        try {
            $input = fopen('php://input', 'rb');
            $body = fopen($bodyPath, 'wb');
            if ($input === false || $body === false) {
                throw new RuntimeException('Unable to read Git request body.');
            }
            stream_copy_to_stream($input, $body);
            fclose($input);
            fclose($body);

            $environment = array_merge(getenv(), [
                'GATEWAY_INTERFACE' => 'CGI/1.1',
                'SERVER_PROTOCOL' => 'HTTP/1.1',
                'SERVER_SOFTWARE' => 'wikicode',
                'REQUEST_METHOD' => $method,
                'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? '',
                'CONTENT_TYPE' => $_SERVER['CONTENT_TYPE'] ?? '',
                'CONTENT_LENGTH' => $_SERVER['CONTENT_LENGTH'] ?? (string) filesize($bodyPath),
                'REMOTE_USER' => $user['username'] ?? '',
                'GIT_PROJECT_ROOT' => dirname($path),
                'GIT_HTTP_EXPORT_ALL' => '1',
                'PATH_INFO' => '/' . $repository['id'] . '.git' . $suffix,
                'HTTP_ACCEPT' => $_SERVER['HTTP_ACCEPT'] ?? '',
                'HTTP_GIT_PROTOCOL' => $_SERVER['HTTP_GIT_PROTOCOL'] ?? '',
                'LC_ALL' => 'C.UTF-8',
            ]);

            set_time_limit(0);
            $process = proc_open(
                ['git', 'http-backend'],
                [0 => ['file', $bodyPath, 'r'], 1 => ['file', $outputPath, 'w'], 2 => ['file', $errorPath, 'w']],
                $pipes,
                null,
                $environment
            );
            if (!is_resource($process)) {
                throw new RuntimeException('Unable to start git http-backend.');
            }
            $status = proc_close($process);
            $process = null;

            $error = file_exists($errorPath) ? (string) file_get_contents($errorPath, false, null, 0, 1048576) : '';
            if ($error !== '') {
                error_log('git http-backend: ' . trim($error));
            }
            if ($status !== 0 && filesize($outputPath) === 0) {
                throw new RuntimeException("git http-backend exited with status {$status}.");
            }
            $this->sendCgiResponse($outputPath, $service);
        } finally {
            if (is_resource($process)) {
                proc_terminate($process);
                proc_close($process);
            }
            foreach ([$bodyPath, $outputPath, $errorPath] as $file) {
                if (is_string($file) && is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    private function sendCgiResponse(string $outputPath, string $service): void
    {
        $output = fopen($outputPath, 'rb');
        if ($output === false) {
            throw new RuntimeException('Unable to read Git response.');
        }

        $headers = '';
        while (!feof($output) && strlen($headers) < 65536) {
            $character = fread($output, 1);
            $headers .= $character;
            if (str_ends_with($headers, "\r\n\r\n") || str_ends_with($headers, "\n\n")) {
                break;
            }
        }
        if (!preg_match('/\r?\n\r?\n/', $headers)) {
            fclose($output);
            throw new RuntimeException('Invalid response from git http-backend.');
        }

        $status = 200;
        $lines = preg_split('/\r?\n/', trim($headers)) ?: [];
        foreach ($lines as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (strcasecmp($name, 'Status') === 0) {
                $status = (int) $value;
                continue;
            }
            if (preg_match('/^[A-Za-z0-9-]+$/', $name)) {
                header($name . ': ' . $value, true);
            }
        }
        http_response_code($status);
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        stream_copy_to_stream($output, fopen('php://output', 'wb'));
        fclose($output);
    }

    private function fail(int $status, string $message): void
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-cache');
        echo $message . "\n";
    }
}
