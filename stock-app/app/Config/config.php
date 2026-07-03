<?php

declare(strict_types=1);

/**
 * Chargement minimal du fichier .env (pas de dépendance externe).
 * Ce fichier doit toujours être inclus via require_once (jamais require) :
 * il est rechargé par plusieurs classes du Core (Database, Session, Mailer...)
 * qui peuvent toutes s'exécuter dans la même requête.
 */
if (!function_exists('loadEnv')) {
    function loadEnv(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if ($key !== '' && getenv($key) === false) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}

$root = dirname(__DIR__, 2);

loadEnv($root . '/.env');

return [
    'app' => [
        'name' => env('APP_NAME', 'Gestion de Stock'),
        'env' => env('APP_ENV', 'production'),
        'url' => env('APP_URL', 'http://localhost'),
        'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
        'root' => $root,
    ],
    'db' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', '3306'),
        'name' => env('DB_NAME', 'stock_app'),
        'user' => env('DB_USER', 'root'),
        'pass' => env('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        'host' => env('MAIL_HOST'),
        'port' => (int) env('MAIL_PORT', 587),
        'user' => env('MAIL_USER'),
        'pass' => env('MAIL_PASS'),
        'from' => env('MAIL_FROM'),
        'from_name' => env('MAIL_FROM_NAME', 'Gestion de Stock'),
    ],
    'session' => [
        'lifetime' => (int) env('SESSION_LIFETIME', 120) * 60,
        'name' => 'stockapp_session',
    ],
    'invitation' => [
        'default_expiry_days' => (int) env('INVITATION_DEFAULT_EXPIRY_DAYS', 7),
    ],
    'password_reset' => [
        'expiry_minutes' => (int) env('PASSWORD_RESET_EXPIRY_MINUTES', 60),
    ],
];
