<?php

namespace App\Core;

class Autoloader
{
    private static $registered = false;

    public static function register($basePath)
    {
        if (self::$registered) {
            return;
        }

        spl_autoload_register(function ($class) use ($basePath) {
            $prefix = 'App\\';
            if (strpos($class, $prefix) !== 0) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
            $file = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $relativePath;

            if (is_file($file)) {
                require_once $file;
            }
        });

        self::$registered = true;
    }
}
