<?php

namespace App\Modules\Chat\Infrastructure;

use App\Core\DatabaseManager;
use PDO;

class ChatHistoryRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = DatabaseManager::connection();
    }

    public function save($userMessage, $response, $ipAddress, $mode, $tokenCount, $modelKey, $providerKey = null)
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO chat_history (ip_address, user, response, jumlah_token, model, provider_key, mode)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $ipAddress,
            $userMessage,
            $response,
            (int) $tokenCount,
            $modelKey,
            $providerKey,
            $mode,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function filteredList(array $filters)
    {
        $limit = max(10, min(100, (int) ($filters['limit'] ?? 20)));
        $offset = max(0, (int) ($filters['offset'] ?? 0));

        list($whereSql, $params) = $this->buildWhereClause($filters);
        $sql = 'SELECT id, ip_address, user, response, jumlah_token, model, provider_key, mode, created_at, updated_at
                FROM chat_history' . $whereSql . ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countFiltered(array $filters)
    {
        list($whereSql, $params) = $this->buildWhereClause($filters);
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM chat_history' . $whereSql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function statistics($startDate = null, $endDate = null)
    {
        $filters = ['start_date' => $startDate, 'end_date' => $endDate];
        list($whereSql, $params) = $this->buildWhereClause($filters);

        $stats = [];

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM chat_history' . $whereSql);
        $stmt->execute($params);
        $stats['total_chats'] = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(jumlah_token), 0) FROM chat_history' . $whereSql);
        $stmt->execute($params);
        $stats['total_tokens'] = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare('SELECT mode, COUNT(*) AS count FROM chat_history' . $whereSql . ' GROUP BY mode');
        $stmt->execute($params);
        $stats['by_mode'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $stmt = $this->pdo->prepare('SELECT model, COUNT(*) AS count FROM chat_history' . $whereSql . ' GROUP BY model');
        $stmt->execute($params);
        $stats['by_model'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $stmt = $this->pdo->query('SELECT COUNT(*) FROM chat_history WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)');
        $stats['last_24h'] = (int) $stmt->fetchColumn();

        return $stats;
    }

    private function buildWhereClause(array $filters)
    {
        $where = [];
        $params = [];

        if (!empty($filters['ip'])) {
            $where[] = 'ip_address = ?';
            $params[] = $filters['ip'];
        }

        if (!empty($filters['mode'])) {
            $where[] = 'mode = ?';
            $params[] = $filters['mode'];
        }

        if (!empty($filters['start_date'])) {
            $where[] = 'DATE(created_at) >= ?';
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $where[] = 'DATE(created_at) <= ?';
            $params[] = $filters['end_date'];
        }

        if (empty($where)) {
            return ['', $params];
        }

        return [' WHERE ' . implode(' AND ', $where), $params];
    }
}
