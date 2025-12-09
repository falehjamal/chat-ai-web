<?php
/**
 * Helper untuk membaca file environment
 */

function loadEnv($filePath = 'config.env') {
    if (!file_exists($filePath)) {
        throw new Exception("File environment tidak ditemukan: $filePath");
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip komentar
        if (strpos($line, '#') === 0) {
            continue;
        }
        
        // Parse key=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Hapus tanda kutip jika ada
            $value = trim($value, '"\'');
            
            // Set sebagai environment variable
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

function getEnvironmentVar($key, $default = null) {
    return $_ENV[$key] ?? (getenv($key) ?: $default);
}
?>
