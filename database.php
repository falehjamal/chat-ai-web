<?php

use App\Core\DatabaseManager;
use App\Modules\Chat\Infrastructure\ChatHistoryRepository;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'bootstrap.php';

class Database
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = DatabaseManager::connection();
    }

    public function connect()
    {
        return $this->pdo;
    }

    public function createChatTable()
    {
        DatabaseManager::boot(__DIR__);
        return true;
    }

    public function saveChatHistory($userMessage, $response, $ipAddress, $mode = 'default', $jumlahToken = 0, $model = 'gpt-5.2')
    {
        $repository = new ChatHistoryRepository();
        return $repository->save($userMessage, $response, $ipAddress, $mode, $jumlahToken, $model, null);
    }

    public function saveMessage($message, $response, $mode = 'default', $jumlahToken = 0, $model = 'gpt-5.2')
    {
        return $this->saveChatHistory($message, $response, self::getRealIpAddress(), $mode, $jumlahToken, $model);
    }

    public function getChatHistory($ipAddress = null, $limit = 50, $offset = 0)
    {
        $sql = 'SELECT id, ip_address, user, response, jumlah_token, model, provider_key, mode, created_at, updated_at
                FROM chat_history';
        $params = [];

        if ($ipAddress) {
            $sql .= ' WHERE ip_address = ?';
            $params[] = $ipAddress;
        }

        $sql .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
        $params[] = (int) $limit;
        $params[] = (int) $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countChatHistory($ipAddress = null)
    {
        $sql = 'SELECT COUNT(*) AS total FROM chat_history';
        $params = [];

        if ($ipAddress) {
            $sql .= ' WHERE ip_address = ?';
            $params[] = $ipAddress;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0);
    }

    public function initialize()
    {
        DatabaseManager::boot(__DIR__);
        return true;
    }

    public static function getRealIpAddress()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $candidate = trim($ips[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }

        if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return 'unknown';
    }
}

function getChatDatabase()
{
    static $database = null;
    if ($database === null) {
        $database = new Database();
    }

    return $database;
}
