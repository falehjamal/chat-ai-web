<?php
/**
 * Database helper untuk Chat AI Web
 * Menangani koneksi database dan operasi CRUD untuk chat history
 */

// Set timezone to Asia/Jakarta (GMT+7)
date_default_timezone_set('Asia/Jakarta');

require_once 'env_helper.php';

class Database {
    private $pdo;
    private $host;
    private $port;
    private $dbname;
    private $username;
    private $password;
    private $charset;

    public function __construct() {
        // Load environment variables jika belum dimuat
        try {
            loadEnv();
        } catch (Exception $e) {
            throw new Exception("Gagal memuat konfigurasi environment");
        }

        // Ambil konfigurasi database dari environment
        $this->host = getEnvironmentVar('DB_HOST', 'localhost');
        $this->port = getEnvironmentVar('DB_PORT', '3306');
        $this->dbname = getEnvironmentVar('DB_NAME', 'chat_ai_web');
        $this->username = getEnvironmentVar('DB_USERNAME', 'root');
        $this->password = getEnvironmentVar('DB_PASSWORD', '');
        $this->charset = getEnvironmentVar('DB_CHARSET', 'utf8mb4');
    }

    /**
     * Membuat koneksi ke database
     */
    public function connect() {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            
            // Set MySQL timezone to Asia/Jakarta (GMT+7)
            $this->pdo->exec("SET time_zone = '+07:00'");
            
            return $this->pdo;
        } catch (PDOException $e) {
            throw new Exception("Koneksi database gagal: " . $e->getMessage());
        }
    }

    /**
     * Membuat tabel chat_history jika belum ada
     */
    public function createChatTable() {
        $pdo = $this->connect();
        
        $sql = "CREATE TABLE IF NOT EXISTS chat_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            user TEXT NOT NULL,
            response TEXT NOT NULL,
            jumlah_token INT DEFAULT 0,
            model VARCHAR(100) DEFAULT 'gpt-5.2',
            mode VARCHAR(20) DEFAULT 'default',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_ip_address (ip_address),
            INDEX idx_created_at (created_at),
            INDEX idx_mode (mode),
            INDEX idx_model (model),
            INDEX idx_token_count (jumlah_token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $pdo->exec($sql);
            
            // Add new columns if they don't exist (for existing tables)
            $alterSqls = [
                "ALTER TABLE chat_history ADD COLUMN IF NOT EXISTS mode VARCHAR(20) DEFAULT 'default'",
                "ALTER TABLE chat_history ADD COLUMN IF NOT EXISTS jumlah_token INT DEFAULT 0",
                "ALTER TABLE chat_history ADD COLUMN IF NOT EXISTS model VARCHAR(100) DEFAULT 'gpt-5.2'"
            ];
            
            foreach ($alterSqls as $alterSql) {
                try {
                    $pdo->exec($alterSql);
                } catch (PDOException $e) {
                    // Column might already exist, ignore error
                }
            }
            
            // Try to rename message column to user if it exists
            try {
                $pdo->exec("ALTER TABLE chat_history CHANGE COLUMN message user TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL");
            } catch (PDOException $e) {
                // Column might already be renamed or not exist, ignore error
            }
            
            return true;
        } catch (PDOException $e) {
            throw new Exception("Gagal membuat tabel chat_history: " . $e->getMessage());
        }
    }

    /**
     * Menyimpan percakapan chat ke database
     */
    public function saveChatHistory($userMessage, $response, $ipAddress, $mode = 'default', $jumlahToken = 0, $model = 'gpt-5.2') {
        $pdo = $this->connect();
        
        $sql = "INSERT INTO chat_history (ip_address, user, response, jumlah_token, model, mode) VALUES (?, ?, ?, ?, ?, ?)";
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$ipAddress, $userMessage, $response, $jumlahToken, $model, $mode]);
            return $pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Gagal menyimpan chat history: " . $e->getMessage());
        }
    }

    /**
     * Method alias untuk kompatibilitas
     */
    public function saveMessage($message, $response, $mode = 'default', $jumlahToken = 0, $model = 'gpt-5.2') {
        $ipAddress = self::getRealIpAddress();
        return $this->saveChatHistory($message, $response, $ipAddress, $mode, $jumlahToken, $model);
    }

    /**
     * Mengambil riwayat chat berdasarkan IP address (opsional)
     */
    public function getChatHistory($ipAddress = null, $limit = 50, $offset = 0) {
        $pdo = $this->connect();
        
        $sql = "SELECT id, ip_address, user, response, jumlah_token, model, mode, created_at, updated_at FROM chat_history";
        $params = [];
        
        if ($ipAddress) {
            $sql .= " WHERE ip_address = ?";
            $params[] = $ipAddress;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Gagal mengambil chat history: " . $e->getMessage());
        }
    }

    /**
     * Menghitung total chat berdasarkan IP address (opsional)
     */
    public function countChatHistory($ipAddress = null) {
        $pdo = $this->connect();
        
        $sql = "SELECT COUNT(*) as total FROM chat_history";
        $params = [];
        
        if ($ipAddress) {
            $sql .= " WHERE ip_address = ?";
            $params[] = $ipAddress;
        }
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result['total'];
        } catch (PDOException $e) {
            throw new Exception("Gagal menghitung chat history: " . $e->getMessage());
        }
    }

    /**
     * Inisialisasi database - membuat tabel jika diperlukan
     */
    public function initialize() {
        $this->createChatTable();
    }

    /**
     * Mendapatkan IP address user yang real dengan validasi
     */
    public static function getRealIpAddress() {
        $ip = null;
        
        // Priority order: REMOTE_ADDR is most reliable in non-proxy environments
        // For proxy environments, check X-Forwarded-For but validate it
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // X-Forwarded-For can contain multiple IPs, get the first one (client IP)
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // Validate IP address format to prevent spoofing with malicious data
        if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        
        // Fallback to REMOTE_ADDR or unknown
        if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            return $_SERVER['REMOTE_ADDR'];
        }
        
        return 'unknown';
    }
}

// Fungsi helper untuk kemudahan penggunaan
function getChatDatabase() {
    static $database = null;
    if ($database === null) {
        $database = new Database();
    }
    return $database;
}

?>
