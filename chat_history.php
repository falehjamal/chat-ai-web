<?php

/**
 * Chat History Viewer
 * Menampilkan riwayat chat yang tersimpan dalam database
 */

// Set timezone to Asia/Jakarta (GMT+7)
date_default_timezone_set('Asia/Jakarta');

require_once 'database.php';
require_once 'env_helper.php';

// Function to get display name for mode
function getModeDisplayName($mode) {
    switch($mode) {
        case 'default': return 'Chat';
        case 'uas': return 'OCR Low';
        case 'uas-math': return 'OCR High';
        default: return ucfirst($mode);
    }
}

// Initialize database
try {
    loadEnv();
    $database = getChatDatabase();
    $database->initialize();
} catch (Exception $e) {
    die("Error initializing database: " . $e->getMessage());
}

// Get parameters
$page = max(1, intval($_GET['page'] ?? 1));
$limit = max(10, min(100, intval($_GET['limit'] ?? 20)));
$ipFilter = $_GET['ip'] ?? null;
$modeFilter = $_GET['mode'] ?? null;

// Date filters - default to last 30 days
$defaultEndDate = date('Y-m-d');
$defaultStartDate = date('Y-m-d', strtotime('-30 days'));
$startDate = $_GET['start_date'] ?? $defaultStartDate;
$endDate = $_GET['end_date'] ?? $defaultEndDate;

$offset = ($page - 1) * $limit;

// Build query with filters
function getChatHistoryWithFilters($database, $ipFilter, $modeFilter, $startDate, $endDate, $limit, $offset) {
    $pdo = $database->connect();
    
    $sql = "SELECT id, ip_address, user, response, jumlah_token, model, mode, created_at, updated_at FROM chat_history WHERE 1=1";
    $params = [];
    
    if ($ipFilter) {
        $sql .= " AND ip_address = ?";
        $params[] = $ipFilter;
    }
    
    if ($modeFilter) {
        $sql .= " AND mode = ?";
        $params[] = $modeFilter;
    }
    
    // Add date filters
    if ($startDate) {
        $sql .= " AND DATE(created_at) >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $sql .= " AND DATE(created_at) <= ?";
        $params[] = $endDate;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function countChatHistoryWithFilters($database, $ipFilter, $modeFilter, $startDate, $endDate) {
    $pdo = $database->connect();
    
    $sql = "SELECT COUNT(*) as total FROM chat_history WHERE 1=1";
    $params = [];
    
    if ($ipFilter) {
        $sql .= " AND ip_address = ?";
        $params[] = $ipFilter;
    }
    
    if ($modeFilter) {
        $sql .= " AND mode = ?";
        $params[] = $modeFilter;
    }
    
    // Add date filters
    if ($startDate) {
        $sql .= " AND DATE(created_at) >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $sql .= " AND DATE(created_at) <= ?";
        $params[] = $endDate;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return $result['total'];
}

// Get data
$chatHistory = getChatHistoryWithFilters($database, $ipFilter, $modeFilter, $startDate, $endDate, $limit, $offset);
$totalRecords = countChatHistoryWithFilters($database, $ipFilter, $modeFilter, $startDate, $endDate);
$totalPages = ceil($totalRecords / $limit);

// Get statistics
function getChatStatistics($database, $startDate = null, $endDate = null) {
    $pdo = $database->connect();
    
    $stats = [];
    $dateCondition = "";
    $params = [];
    
    // Add date filters to statistics
    if ($startDate) {
        $dateCondition .= " AND DATE(created_at) >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $dateCondition .= " AND DATE(created_at) <= ?";
        $params[] = $endDate;
    }
    
    // Total chats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM chat_history WHERE 1=1" . $dateCondition);
    $stmt->execute($params);
    $stats['total_chats'] = $stmt->fetch()['total'];
    
    // Total tokens
    $stmt = $pdo->prepare("SELECT SUM(jumlah_token) as total_tokens FROM chat_history WHERE 1=1" . $dateCondition);
    $stmt->execute($params);
    $stats['total_tokens'] = $stmt->fetch()['total_tokens'] ?? 0;
    
    // Chats by mode
    $stmt = $pdo->prepare("SELECT mode, COUNT(*) as count FROM chat_history WHERE 1=1" . $dateCondition . " GROUP BY mode");
    $stmt->execute($params);
    $stats['by_mode'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Chats by model
    $stmt = $pdo->prepare("SELECT model, COUNT(*) as count FROM chat_history WHERE 1=1" . $dateCondition . " GROUP BY model");
    $stmt->execute($params);
    $stats['by_model'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Recent activity (last 24 hours)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM chat_history WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stats['last_24h'] = $stmt->fetch()['count'];
    
    return $stats;
}

$stats = getChatStatistics($database, $startDate, $endDate);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat History - Chat AI Web</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #4CAF50;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .filters form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-group label {
            margin-bottom: 5px;
            font-weight: 500;
        }
        .filter-group input, .filter-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .chat-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .chat-table th, .chat-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .chat-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        .chat-table tr:hover {
            background-color: #f5f5f5;
        }
        .message-preview {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .response-preview {
            max-width: 400px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .mode-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }
        .mode-default { background-color: #6c757d; }
        .mode-uas { background-color: #17a2b8; }
        .mode-uas-math { background-color: #dc3545; }
        .model-badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 11px;
            background-color: #e9ecef;
            color: #495057;
        }
        .token-count {
            text-align: right;
            font-weight: 500;
            color: #495057;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        .pagination a {
            padding: 8px 12px;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #4CAF50;
        }
        .pagination a:hover {
            background: #f8f9fa;
        }
        .pagination .current {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
        .ip-address {
            font-family: monospace;
            font-size: 13px;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .timestamp {
            font-size: 13px;
            color: #6c757d;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .copyable {
            cursor: pointer;
            position: relative;
            padding: 2px 4px;
            border-radius: 3px;
            transition: background-color 0.2s;
        }
        .copyable:hover {
            background-color: #e3f2fd;
        }
        .copyable::after {
            content: '📋';
            position: absolute;
            right: -20px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0;
            transition: opacity 0.2s;
            font-size: 12px;
        }
        .copyable:hover::after {
            opacity: 1;
        }
        .copy-success {
            background-color: #c8e6c9 !important;
            transition: background-color 0.3s;
        }
        .date-range {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .date-range input {
            width: 140px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Chat History Dashboard</h1>
        
        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total_chats']) ?></div>
                <div class="stat-label">Total Chats</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total_tokens']) ?></div>
                <div class="stat-label">Total Tokens</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['last_24h']) ?></div>
                <div class="stat-label">Last 24 Hours</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count($stats['by_model']) ?></div>
                <div class="stat-label">Models Used</div>
            </div>
        </div>

        <!-- Mode Statistics -->
        <?php if (!empty($stats['by_mode'])): ?>
        <div class="stats">
            <?php foreach ($stats['by_mode'] as $mode => $count): ?>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($count) ?></div>
                <div class="stat-label">Mode <?= ucfirst($mode) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters">
            <form method="GET">
                <div class="filter-group">
                    <label>Date Range:</label>
                    <div class="date-range">
                        <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" title="Start Date">
                        <span>to</span>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" title="End Date">
                    </div>
                </div>
                <div class="filter-group">
                    <label for="ip">IP Address:</label>
                    <input type="text" id="ip" name="ip" value="<?= htmlspecialchars($ipFilter ?? '') ?>" placeholder="Filter by IP">
                </div>
                <div class="filter-group">
                    <label for="mode">Mode:</label>
                    <select id="mode" name="mode">
                        <option value="">All Modes</option>
                        <option value="default" <?= $modeFilter === 'default' ? 'selected' : '' ?>>Chat</option>
                        <option value="uas" <?= $modeFilter === 'uas' ? 'selected' : '' ?>>OCR Low</option>
                        <option value="uas-math" <?= $modeFilter === 'uas-math' ? 'selected' : '' ?>>OCR High</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="limit">Per Page:</label>
                    <select id="limit" name="limit">
                        <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                        <option value="20" <?= $limit == 20 ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </div>
                <button type="submit" class="btn">Filter</button>
                <a href="?" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        <!-- Results Info -->
        <p>Showing <?= count($chatHistory) ?> of <?= number_format($totalRecords) ?> chat records (Page <?= $page ?> of <?= $totalPages ?>)</p>
        <p><strong>Date Range:</strong> <?= htmlspecialchars($startDate) ?> to <?= htmlspecialchars($endDate) ?> (<?= ceil((strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24)) + 1 ?> days)</p>

        <!-- Chat History Table -->
        <?php if (!empty($chatHistory)): ?>
        <table class="chat-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>IP Address</th>
                    <th>User Message</th>
                    <th>Bot Response</th>
                    <th>Tokens</th>
                    <th>Model</th>
                    <th>Mode</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($chatHistory as $chat): ?>
                <tr>
                    <td><?= $chat['id'] ?></td>
                    <td><span class="ip-address"><?= htmlspecialchars($chat['ip_address']) ?></span></td>
                    <td>
                        <div class="message-preview copyable" 
                             title="Click to copy: <?= htmlspecialchars($chat['user']) ?>"
                             data-copy-text="<?= htmlspecialchars($chat['user']) ?>">
                            <?= htmlspecialchars(mb_substr($chat['user'], 0, 100)) ?><?= mb_strlen($chat['user']) > 100 ? '...' : '' ?>
                        </div>
                    </td>
                    <td>
                        <div class="response-preview copyable" 
                             title="Click to copy: <?= htmlspecialchars($chat['response']) ?>"
                             data-copy-text="<?= htmlspecialchars($chat['response']) ?>">
                            <?= htmlspecialchars(mb_substr($chat['response'], 0, 150)) ?><?= mb_strlen($chat['response']) > 150 ? '...' : '' ?>
                        </div>
                    </td>
                    <td class="token-count"><?= number_format($chat['jumlah_token']) ?></td>
                    <td><span class="model-badge"><?= htmlspecialchars($chat['model']) ?></span></td>
                    <td><span class="mode-badge mode-<?= $chat['mode'] ?>"><?= getModeDisplayName($chat['mode']) ?></span></td>
                    <td class="timestamp"><?= date('Y-m-d H:i:s', strtotime($chat['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo; Previous</a>
            <?php endif; ?>
            
            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            
            for ($i = $start; $i <= $end; $i++):
            ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                   class="<?= $i == $page ? 'current' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="no-data">
            <h3>No chat history found</h3>
            <p>Try adjusting your filters or check back later.</p>
        </div>
        <?php endif; ?>

        <div style="margin-top: 40px; text-align: center; color: #6c757d; border-top: 1px solid #ddd; padding-top: 20px;">
            <p><a href="index.php" class="btn">← Back to Chat</a> | Generated on <?= date('Y-m-d H:i:s') ?></p>
        </div>
    </div>

    <script>
        // Copy to clipboard functionality
        document.addEventListener('DOMContentLoaded', function() {
            const copyableElements = document.querySelectorAll('.copyable');
            
            copyableElements.forEach(element => {
                element.addEventListener('click', function() {
                    const textToCopy = this.getAttribute('data-copy-text');
                    
                    // Create a temporary textarea to hold the text
                    const tempTextArea = document.createElement('textarea');
                    tempTextArea.value = textToCopy;
                    document.body.appendChild(tempTextArea);
                    
                    // Select and copy the text
                    tempTextArea.select();
                    tempTextArea.setSelectionRange(0, 99999); // For mobile devices
                    
                    try {
                        const successful = document.execCommand('copy');
                        if (successful) {
                            // Visual feedback
                            this.classList.add('copy-success');
                            setTimeout(() => {
                                this.classList.remove('copy-success');
                            }, 1000);
                            
                            // Show tooltip
                            const originalTitle = this.title;
                            this.title = '✅ Copied to clipboard!';
                            setTimeout(() => {
                                this.title = originalTitle;
                            }, 2000);
                        }
                    } catch (err) {
                        console.error('Failed to copy text: ', err);
                        alert('Failed to copy text to clipboard');
                    }
                    
                    // Remove the temporary textarea
                    document.body.removeChild(tempTextArea);
                });
            });
        });

        // Auto-submit form when date inputs change
        document.addEventListener('DOMContentLoaded', function() {
            const dateInputs = document.querySelectorAll('input[type="date"]');
            dateInputs.forEach(input => {
                input.addEventListener('change', function() {
                    // Add a small delay to allow user to set both dates
                    setTimeout(() => {
                        this.form.submit();
                    }, 500);
                });
            });
        });
    </script>
</body>
</html>
