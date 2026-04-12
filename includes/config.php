<?php
function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

loadEnv(dirname(__DIR__) . '/.env');

define('DB_HOST',    $_ENV['DB_HOST']    ?? 'localhost');
define('DB_PORT',    $_ENV['DB_PORT']    ?? '3306');
define('DB_USER',    $_ENV['DB_USER']    ?? 'root');
define('DB_PASS',    $_ENV['DB_PASS']    ?? '');
define('DB_NAME',    $_ENV['DB_NAME']    ?? 'padrao_claude');
define('APP_SECRET', $_ENV['APP_SECRET'] ?? 'secret');
define('APP_URL',    $_ENV['APP_URL']    ?? 'http://localhost/padrao-claude');
