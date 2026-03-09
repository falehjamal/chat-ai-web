<?php

use App\Core\Autoloader;
use App\Core\DatabaseManager;
use App\Core\Env;

$appRoot = dirname(__DIR__, 2);

require_once $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Autoloader.php';

Autoloader::register($appRoot);

Env::load($appRoot . DIRECTORY_SEPARATOR . 'config.env');
date_default_timezone_set(Env::get('APP_TIMEZONE', 'Asia/Jakarta'));
DatabaseManager::boot($appRoot);
