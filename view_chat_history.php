<?php
/**
 * View Chat History - Melihat riwayat percakapan yang tersimpan
 * File ini untuk debugging dan verifikasi data tersimpan dengan benar
 */

require_once 'database.php';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter berdasarkan IP (opsional)
$filterIp = isset($_GET['ip']) ? $_GET['ip'] : null;

?><!DOCTYPE html>
<html>
<head>
    <title>Chat History - Chat AI Web</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .chat-item { border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px; }
        .user-message { background-color: #f0f8ff; }
        .ai-response { background-color: #f8f8f8; }
        .meta { color: #666; font-size: 12px; margin-bottom: 10px; }
        .pagination { margin: 20px 0; }
        .pagination a { margin: 0 5px; padding: 5px 10px; text-decoration: none; border: 1px solid #ddd; }
        .pagination .current { background-color: #007cba; color: white; }
        .filter { margin-bottom: 20px; }
        .stats { background-color: #e7f3ff; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Riwayat Percakapan Chat AI Web</h1>
    
    <?php
    try {
        $database = getChatDatabase();
        
        // Statistik
        $totalChats = $database->countChatHistory($filterIp);
        $totalPages = ceil($totalChats / $limit);
        
        echo "<div class='stats'>";
        echo "<strong>Total Percakapan:</strong> $totalChats";
        if ($filterIp) {
            echo " (Filter IP: $filterIp)";
        }
        echo "</div>";
        
        // Filter form
        echo "<div class='filter'>";
        echo "<form method='GET'>";
        echo "Filter berdasarkan IP: ";
        echo "<input type='text' name='ip' value='" . htmlspecialchars($filterIp ?? '') . "' placeholder='Masukkan IP address'>";
        echo "<input type='submit' value='Filter'>";
        if ($filterIp) {
            echo " <a href='?'>Hapus Filter</a>";
        }
        echo "</form>";
        echo "</div>";
        
        // Ambil data chat
        $chatHistory = $database->getChatHistory($filterIp, $limit, $offset);
        
        if (empty($chatHistory)) {
            echo "<p>Belum ada riwayat percakapan.</p>";
        } else {
            foreach ($chatHistory as $chat) {
                echo "<div class='chat-item'>";
                echo "<div class='meta'>";
                echo "ID: " . htmlspecialchars($chat['id']) . " | ";
                echo "IP: " . htmlspecialchars($chat['ip_address']) . " | ";
                echo "Waktu: " . htmlspecialchars($chat['created_at']);
                if ($chat['updated_at'] !== $chat['created_at']) {
                    echo " (Updated: " . htmlspecialchars($chat['updated_at']) . ")";
                }
                echo "</div>";
                
                echo "<div class='user-message'>";
                echo "<strong>User:</strong><br>";
                echo nl2br(htmlspecialchars($chat['message']));
                echo "</div>";
                
                echo "<div class='ai-response'>";
                echo "<strong>AI:</strong><br>";
                echo nl2br(htmlspecialchars($chat['response']));
                echo "</div>";
                
                echo "</div>";
            }
            
            // Pagination
            if ($totalPages > 1) {
                echo "<div class='pagination'>";
                echo "Halaman: ";
                
                for ($i = 1; $i <= $totalPages; $i++) {
                    $url = "?page=$i";
                    if ($filterIp) {
                        $url .= "&ip=" . urlencode($filterIp);
                    }
                    
                    if ($i == $page) {
                        echo "<span class='current'>$i</span>";
                    } else {
                        echo "<a href='$url'>$i</a>";
                    }
                }
                
                echo "</div>";
            }
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>";
        echo "<h3>Error:</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Pastikan database sudah dikonfigurasi dengan benar di config.env</p>";
        echo "</div>";
    }
    ?>
    
    <hr>
    <p><a href="index.php">← Kembali ke Chat</a></p>
</body>
</html> 
