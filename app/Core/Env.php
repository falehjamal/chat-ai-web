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
            error_log('[faleh-ai] File environment tidak ditemukan: ' . $filePath);
            throw new Exception('File environment tidak ditemukan.');
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            error_log('[faleh-ai] Gagal membaca file environment: ' . $filePath);
            throw new Exception('Gagal membaca file environment.');
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

    public static function isDebug()
    {
        $value = strtolower(trim((string) self::get('DEBUG', 'false')));
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }
}
