<?php
// app/Core/Database.php
// Robust PDO Singleton for MySQL/MariaDB with automatic query telemetry.

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    private static ?PDO $pdo = null;
    private static array $config = [];

    public static function configure(array $config): void
    {
        self::$config = $config;
    }

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $dbCfg = self::$config['db'] ?? [
                'host' => '127.0.0.1',
                'port' => 3306,
                'database' => 'ecommerce_db',
                'username' => 'ecom_user',
                'password' => 'ecom_pass',
                'charset' => 'utf8mb4',
            ];

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $dbCfg['host'],
                $dbCfg['port'] ?? 3306,
                $dbCfg['database'],
                $dbCfg['charset'] ?? 'utf8mb4'
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                self::$pdo = new PDO($dsn, $dbCfg['username'], $dbCfg['password'], $options);
            } catch (PDOException $e) {
                // Keep dev terminal clean; log error and present friendly message
                error_log('[DB Error] Connection failed: ' . $e->getMessage());
                throw new \RuntimeException('Database connection error. Please verify database credentials or run "make dev".');
            }
        }

        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $start = microtime(true);
        $pdo = self::getConnection();

        try {
            if (empty($params)) {
                $stmt = $pdo->query($sql);
            } else {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }
        } finally {
            $durationMs = (microtime(true) - $start) * 1000;
            Telemetry::recordSql($sql, $params, $durationMs);
        }

        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $res = self::query($sql, $params)->fetch();
        return $res ?: null;
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    public static function lastInsertId(): string
    {
        return self::getConnection()->lastInsertId();
    }

    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    public static function rollBack(): bool
    {
        if (self::getConnection()->inTransaction()) {
            return self::getConnection()->rollBack();
        }
        return false;
    }
}
