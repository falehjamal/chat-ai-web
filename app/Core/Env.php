<?php

namespace App\Core;

use Exception;

class Env
{
    private static $loaded = false;
    private static $values = [];

    public static function load($filePath)
    {
        if (self::$loaded) {
            return;
        }

        if (!is_file($filePath)) {
            throw new Exception('File environment tidak ditemukan: ' . $filePath);
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new Exception('Gagal membaca file environment: ' . $filePath);
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, '=') === false) {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $value = trim($value, "\"'");

            self::$values[$key] = $value;
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }

        self::$loaded = true;
    }

    public static function get($key, $default = null)
    {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }

        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }

        return $_ENV[$key] ?? $default;
    }
}
