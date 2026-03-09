<?php

namespace App\Core;

use PDO;

class SqlMigrationRunner
{
    private $pdo;
    private $migrationPath;

    public function __construct(PDO $pdo, $migrationPath)
    {
        $this->pdo = $pdo;
        $this->migrationPath = $migrationPath;
    }

    public function run()
    {
        $this->ensureMigrationTable();

        $files = glob(rtrim($this->migrationPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.sql');
        if ($files === false) {
            return;
        }

        sort($files, SORT_STRING);

        foreach ($files as $file) {
            $name = basename($file);
            if ($this->hasRun($name)) {
                continue;
            }

            $this->runFile($file);
            $this->markAsRun($name);
        }
    }

    private function ensureMigrationTable()
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS app_migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private function hasRun($name)
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM app_migrations WHERE migration_name = ?');
        $stmt->execute([$name]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function markAsRun($name)
    {
        $stmt = $this->pdo->prepare('INSERT INTO app_migrations (migration_name) VALUES (?)');
        $stmt->execute([$name]);
    }

    private function runFile($file)
    {
        $sql = file_get_contents($file);
        if ($sql === false) {
            return;
        }

        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        $statements = preg_split('/;[\r\n]+/', $sql);

        if (!is_array($statements)) {
            return;
        }

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }

            $this->pdo->exec($statement);
        }
    }
}
