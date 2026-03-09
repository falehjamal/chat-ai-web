<?php

use App\Core\Autoloader;
use App\Core\Env;

$appRoot = __DIR__;
require_once $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Autoloader.php';
Autoloader::register($appRoot);

function loadEnv($filePath = 'config.env')
{
    $resolved = $filePath;
    if (!preg_match('/^[A-Za-z]:\\\\|^\//', $filePath)) {
        $resolved = __DIR__ . DIRECTORY_SEPARATOR . ltrim($filePath, DIRECTORY_SEPARATOR);
    }

    Env::load($resolved);
}

function getEnvironmentVar($key, $default = null)
{
    return Env::get($key, $default);
}
