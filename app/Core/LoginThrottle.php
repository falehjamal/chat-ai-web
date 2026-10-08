<?php

namespace App\Core;

class LoginThrottle
{
    const MAX_ATTEMPTS = 5;
    const WINDOW_SECONDS = 900;

    public static function assertAllowed($ip)
    {
        $state = self::stateFor($ip);
        if ($state['count'] >= self::MAX_ATTEMPTS) {
            throw new PublicException('Terlalu banyak percobaan login. Coba lagi beberapa menit lagi.');
        }
    }

    public static function hit($ip)
    {
        self::update($ip, function ($state) {
            $state['count']++;
            return $state;
        });
    }

    public static function clear($ip)
    {
        self::update($ip, function () {
            return ['count' => 0, 'first' => time()];
        });
    }

    public static function consumeAllowance($bucket, $ip, $max, $seconds)
    {
        $bucket = preg_replace('/[^a-z0-9_-]/', '', (string) $bucket);
        if ($bucket === '') {
            $bucket = 'requests';
        }

        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $bucket . '.json';
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            return true;
        }

        $handle = fopen($path, 'c+');
        if ($handle === false || !flock($handle, LOCK_EX)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            return true;
        }

        try {
            $all = json_decode(stream_get_contents($handle) ?: '', true);
            if (!is_array($all)) {
                $all = [];
            }

            $now = time();
            $key = hash('sha256', (string) $ip);
            foreach ($all as $storedKey => $storedState) {
                if (($now - (int) ($storedState['first'] ?? 0)) >= $seconds) {
                    unset($all[$storedKey]);
                }
            }

            $state = $all[$key] ?? ['count' => 0, 'first' => $now];
            if (($now - (int) $state['first']) >= $seconds) {
                $state = ['count' => 0, 'first' => $now];
            }

            if ($state['count'] >= $max) {
                return false;
            }

            $state['count']++;
            $all[$key] = $state;
            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($all));
            fflush($handle);
            return true;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private static function stateFor($ip)
    {
        $all = self::readAll();
        $key = self::key($ip);
        $state = $all[$key] ?? ['count' => 0, 'first' => time()];

        if ((time() - (int) $state['first']) >= self::WINDOW_SECONDS) {
            return ['count' => 0, 'first' => time()];
        }

        return $state;
    }

    private static function update($ip, callable $mutator)
    {
        $path = self::path();
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            error_log('[faleh-ai] Folder throttle login tidak bisa dibuat.');
            return;
        }

        $handle = fopen($path, 'c+');
        if ($handle === false) {
            error_log('[faleh-ai] File throttle login tidak bisa dibuka.');
            return;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return;
            }

            $contents = stream_get_contents($handle);
            $all = json_decode($contents ?: '', true);
            if (!is_array($all)) {
                $all = [];
            }

            $now = time();
            foreach ($all as $storedKey => $storedState) {
                $first = (int) ($storedState['first'] ?? 0);
                if (($now - $first) >= self::WINDOW_SECONDS) {
                    unset($all[$storedKey]);
                }
            }

            $key = self::key($ip);
            $state = $all[$key] ?? ['count' => 0, 'first' => $now];
            if (($now - (int) $state['first']) >= self::WINDOW_SECONDS) {
                $state = ['count' => 0, 'first' => $now];
            }

            $all[$key] = $mutator($state);

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($all));
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private static function readAll()
    {
        $path = self::path();
        if (!is_file($path)) {
            return [];
        }

        $contents = file_get_contents($path);
        $all = json_decode($contents ?: '', true);
        return is_array($all) ? $all : [];
    }

    private static function key($ip)
    {
        return hash('sha256', (string) $ip);
    }

    private static function path()
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'login-attempts.json';
    }
}
