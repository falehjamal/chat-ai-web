<?php

namespace App\Core;

use RuntimeException;

class Csrf
{
    const SESSION_KEY = '_csrf_token';

    public static function token()
    {
        Session::start();

        $token = Session::get(self::SESSION_KEY);
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::put(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public static function verify($token)
    {
        $stored = Session::get(self::SESSION_KEY);
        return is_string($stored) && is_string($token) && hash_equals($stored, $token);
    }

    public static function requireValid($token)
    {
        if (!self::verify($token)) {
            throw new RuntimeException('Token CSRF tidak valid.');
        }
    }
}
