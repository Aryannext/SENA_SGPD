<?php

declare(strict_types=1);

namespace Core;

/**
 * Base model providing a shared PDO singleton.
 *
 * Dependency Inversion: controllers depend on this abstraction,
 * not on raw PDO calls scattered across the codebase.
 */
class Model
{
    private static ?\PDO $pdo = null;

    /**
     * Get or create the shared PDO connection.
     */
    protected static function db(): \PDO
    {
        if (self::$pdo === null) {
            $cfg = require dirname(__DIR__) . '/config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $cfg['host'],
                $cfg['port'],
                $cfg['dbname'],
                $cfg['charset']
            );

            self::$pdo = new \PDO($dsn, $cfg['username'], $cfg['password'], $cfg['options']);
        }

        return self::$pdo;
    }

    /**
     * Sustituye la conexión compartida.
     *
     * Existe para que las pruebas de integración apunten a una base de datos
     * desechable en lugar de a la de trabajo. Pasar null restaura el
     * comportamiento normal (leer config/database.php).
     */
    public static function setConnection(?\PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * Run a prepared statement and return all rows.
     *
     * @param string              $sql
     * @param array<int|string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    protected static function query(string $sql, array $params = []): array
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Run a prepared statement and return a single row.
     *
     * @return array<string, mixed>|null
     */
    protected static function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Execute an INSERT/UPDATE/DELETE and return affected rows.
     */
    protected static function execute(string $sql, array $params = []): int
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Get the last inserted ID.
     */
    protected static function lastInsertId(): string
    {
        return static::db()->lastInsertId();
    }
}
