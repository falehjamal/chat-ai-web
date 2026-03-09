<?php

namespace App\Modules\Admin\Application;

use App\Core\Session;
use App\Core\View;
use App\Modules\Admin\Infrastructure\AdminUserRepository;
use Exception;

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
        $user = $this->users->findByUsername($username);
        if (!$user || empty($user['is_active'])) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

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
            throw new Exception('Admin awal sudah dibuat.');
        }

        $username = strtolower(trim($username));
        if ($username === '' || trim($password) === '' || trim($displayName) === '') {
            throw new Exception('Semua field admin wajib diisi.');
        }

        if (strlen($password) < 8) {
            throw new Exception('Password admin minimal 8 karakter.');
        }

        $id = $this->users->create($username, password_hash($password, PASSWORD_DEFAULT), trim($displayName));
        Session::put(self::SESSION_KEY, $id);
        return $id;
    }
}
