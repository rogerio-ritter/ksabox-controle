<?php
/**
 * config.php — Carrega variáveis do .env e define constantes da aplicação
 */

// Carregar .env manualmente (sem dependências externas)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        if (!empty($key)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Constantes de banco
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'ksabox_controle');

// Constantes de aplicação
define('APP_SECRET', getenv('APP_SECRET') ?: 'ksabox_secret_2024');
define('APP_URL',    getenv('APP_URL')    ?: 'http://localhost/ksabox-controle');
define('APP_NAME',   'Ksabox - Sistema de Gestão');

// Configurações de sessão segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// Fuso horário padrão Brasil
date_default_timezone_set('America/Sao_Paulo');

// Exibir erros apenas em desenvolvimento (comentar em produção)
ini_set('display_errors', 1);
error_reporting(E_ALL);
