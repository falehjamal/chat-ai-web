<?php

use App\Core\Autoloader;
use App\Core\DatabaseManager;
use App\Core\Env;

$appRoot = dirname(__DIR__, 2);

require_once $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Autoloader.php';

Autoloader::register($appRoot);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

set_exception_handler(function ($exception) {
    error_log('[faleh-ai] ' . $exception->getMessage());

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $exception->getMessage() . PHP_EOL);
        exit(1);
    }

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }

    echo Env::isDebug() ? $exception->getMessage() : 'Terjadi kesalahan. Silakan coba lagi.';
    exit(1);
});

Env::load($appRoot . DIRECTORY_SEPARATOR . '.env');
date_default_timezone_set(Env::get('APP_TIMEZONE', 'Asia/Jakarta'));
ini_set('display_errors', Env::isDebug() ? '1' : '0');

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

DatabaseManager::boot($appRoot);
