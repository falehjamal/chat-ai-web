<?php

namespace App\Core;

use Throwable;

class ErrorPresenter
{
    public static function message(Throwable $throwable, $fallback = 'Terjadi kesalahan. Silakan coba lagi.')
    {
        if (Env::isDebug() || $throwable instanceof PublicException) {
            return $throwable->getMessage();
        }

        error_log('[faleh-ai] ' . $throwable->getMessage());
        return $fallback;
    }
}
