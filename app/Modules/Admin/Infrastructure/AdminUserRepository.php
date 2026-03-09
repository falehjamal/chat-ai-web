<?php

namespace App\Modules\Admin\Infrastructure;

use App\Core\DatabaseManager;
use PDO;

class AdminUserRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = DatabaseManager::connection();
    }

    public function hasAnyUser()
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM admin_users');
        return (int) $stmt->fetchColumn() > 0;
    }

    public function findByUsername($username)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findById($id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admin_users WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create($username, $passwordHash, $displayName)
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_users (username, password_hash, display_name, is_active)
             VALUES (?, ?, ?, 1)'
        );
        $stmt->execute([$username, $passwordHash, $displayName]);
        return (int) $this->pdo->lastInsertId();
    }
}
