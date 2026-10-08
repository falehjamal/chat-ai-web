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

        $user = $this->users->findByUsername($username);
        if (!$user || empty($user['is_active']) || !password_verify($password, $user['password_hash'])) {
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

        return $this->users->findById($id);
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
        if ($username === '' || trim($password) === '' || trim($displayName) === '') {
            throw new PublicException('Semua field admin wajib diisi.');
        }

        if (strlen($password) < 8) {
            throw new PublicException('Password admin minimal 8 karakter.');
        }

        $id = $this->users->create($username, password_hash($password, PASSWORD_DEFAULT), trim($displayName));
        Session::regenerate();
        Session::put(self::SESSION_KEY, $id);
        return $id;
    }
}
