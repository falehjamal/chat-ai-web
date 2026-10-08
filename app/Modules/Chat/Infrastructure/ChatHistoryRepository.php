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

        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS total_chats, COALESCE(SUM(jumlah_token), 0) AS total_tokens FROM chat_history' . $whereSql);
        $stmt->execute($params);
        $totals = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $stats['total_chats'] = (int) ($totals['total_chats'] ?? 0);
        $stats['total_tokens'] = (int) ($totals['total_tokens'] ?? 0);

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

        $ip = trim((string) ($filters['ip'] ?? ''));
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
            $where[] = 'ip_address = ?';
            $params[] = $ip;
        }

        $mode = (string) ($filters['mode'] ?? '');
        if ($mode !== '' && in_array($mode, ['default', 'uas', 'uas-math'], true)) {
            $where[] = 'mode = ?';
            $params[] = $mode;
        }

        $startDate = (string) ($filters['start_date'] ?? '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $where[] = 'created_at >= ?';
            $params[] = $startDate . ' 00:00:00';
        }

        $endDate = (string) ($filters['end_date'] ?? '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $where[] = 'created_at < DATE_ADD(?, INTERVAL 1 DAY)';
            $params[] = $endDate . ' 00:00:00';
        }

        if (empty($where)) {
            return ['', $params];
        }

        return [' WHERE ' . implode(' AND ', $where), $params];
    }
}
