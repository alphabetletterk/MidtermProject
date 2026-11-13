<?php
function loadEnv(string $path): void
{
    if (!is_readable($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

function env(string $key): ?string
{
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }
    return $_ENV[$key] ?? null;
}

loadEnv(__DIR__ . '/../.env');

$showErrors = in_array(strtolower((string) env('APP_ENV')), ['local', 'development'], true);

error_reporting(E_ALL);
ini_set('display_errors', $showErrors ? '1' : '0');
ini_set('display_startup_errors', $showErrors ? '1' : '0');
ini_set('log_errors', '1');

if (!$showErrors) {
    set_exception_handler(function (Throwable $e): void {
        error_log((string) $e);
        $message = 'Something went wrong. Please try again later.';
        $popup = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: ' . ($popup ? 'text/plain' : 'text/html') . '; charset=UTF-8');
        }
        if ($popup) {
            echo $message;
            return;
        }
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1"><title>Error</title></head>'
           . '<body style="font-family: system-ui, sans-serif; padding: 2rem;">'
           . '<h1 style="font-size: 1.5rem;">Something went wrong</h1><p>' . $message . '</p>'
           . '<p><a href="javascript:history.back()">Go back</a></p></body></html>';
    });
}