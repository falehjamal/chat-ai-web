<?php

namespace App\Modules\Admin\Application;

use App\Core\LoginThrottle;
use App\Core\PublicException;
use App\Core\Session;
use App\Core\View;
use App\Modules\Admin\Infrastructure\AdminUserRepository;

class AdminAuthService
{
    const SESSION_KEY = 'admin_user_id';

    private $users;

    public function __construct()
    {
        $this->users = new AdminUserRepository();
    }

    public function hasAnyAdmin()
    {
        return $this->users->hasAnyUser();
    }

    public function attempt($username, $password)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        LoginThrottle::assertAllowed($ip);

        $user = $this->users->findByUsername(strtolower(trim($username)));
        $hash = $user['password_hash'] ?? '$2y$10$KIRnXpU7897K0XEGAWlDPO5jd/U0pbygN8icOrNLRtwIsDhhgXF4.';
        $valid = $user && !empty($user['is_active']) && password_verify($password, $hash);
        if (!$valid) {
            LoginThrottle::hit($ip);
            return false;
        }

        LoginThrottle::clear($ip);
        Session::regenerate();
        Session::put(self::SESSION_KEY, (int) $user['id']);
        return true;
    }

    public function currentUser()
    {
        $id = Session::get(self::SESSION_KEY);
        if (!$id) {
            return null;
        }

        $user = $this->users->findById($id);
        if (!$user) {
            return null;
        }

        unset($user['password_hash']);
        return $user;
    }

    public function requireAuth($redirect = '/admin/login.php')
    {
        if ($this->currentUser()) {
            return;
        }

        View::redirect($redirect);
    }

    public function logout()
    {
        Session::forget(self::SESSION_KEY);
    }

    public function createInitialAdmin($username, $password, $displayName)
    {
        if ($this->hasAnyAdmin()) {
            throw new PublicException('Admin awal sudah dibuat.');
        }

        $username = strtolower(trim($username));
        $displayName = trim($displayName);
        $password = (string) $password;
        if (!preg_match('/^[a-z0-9._-]{3,32}$/', $username) || $displayName === '' || strlen($displayName) > 80) {
            throw new PublicException('Username 3–32 karakter (huruf, angka, titik, garis). Nama tampilan wajib diisi.');
        }

        if (strlen($password) < 8 || strlen($password) > 128) {
            throw new PublicException('Password admin 8–128 karakter.');
        }

        $id = $this->users->create($username, password_hash($password, PASSWORD_DEFAULT), trim($displayName));
        Session::regenerate();
        Session::put(self::SESSION_KEY, $id);
        return $id;
    }
}
