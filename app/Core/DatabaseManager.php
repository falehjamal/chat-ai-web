<?php

namespace App\Core;

use PDO;
use PDOException;
use Exception;

class DatabaseManager
{
    private static $pdo;
    private static $booted = false;
    private static $rootPath;

    public static function boot($rootPath)
    {
        self::$rootPath = $rootPath;

        if (self::$booted) {
            return;
        }

        self::runMigrations();
        self::$booted = true;
    }

    public static function connection()
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = Env::get('DB_HOST', 'localhost');
        $port = Env::get('DB_PORT', '3306');
        $dbname = Env::get('DB_NAME', 'chat_ai_web');
        $username = Env::get('DB_USERNAME', 'root');
        $password = Env::get('DB_PASSWORD', '');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');

        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $host,
                $port,
                $dbname,
                $charset
            );

            self::$pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            self::$pdo->exec("SET time_zone = '+07:00'");

            return self::$pdo;
        } catch (PDOException $exception) {
            throw new Exception('Koneksi database gagal: ' . $exception->getMessage());
        }
    }

    private static function runMigrations()
    {
        $runner = new SqlMigrationRunner(
            self::connection(),
            self::$rootPath . DIRECTORY_SEPARATOR . 'migrations'
        );
        $runner->run();
    }
}
