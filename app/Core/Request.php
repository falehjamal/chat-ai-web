<?php

namespace App\Core;

class Request
{
    public function method()
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function isPost()
    {
        return $this->method() === 'POST';
    }

    public function query($key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public function input($key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    public function json()
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return [];
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
