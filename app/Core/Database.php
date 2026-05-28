<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;
use App\Exceptions\DatabaseException;

/**
 * Database connection manager — Singleton pattern.
 *
 * Guarantees a single PDO instance throughout the request lifecycle.
 * Cloning and unserialization are explicitly blocked to prevent
 * accidental Singleton bypass.
 *
 * Usage:
 *   $pdo = Database::getInstance()->getConnection();
 */
final class Database
{
    /** @var self|null The sole instance. */
    private static ?self $instance = null;

    /** @var PDO The underlying PDO connection. */
    private PDO $connection;

    /**
     * Private constructor: reads database config and opens the PDO connection.
     *
     * PDO options applied:
     *  - ERRMODE_EXCEPTION   : all DB errors throw PDOException
     *  - FETCH_ASSOC         : fetchAll() returns associative arrays by default
     *  - EMULATE_PREPARES=false : real server-side prepared statements (SQL injection prevention)
     *  - PERSISTENT=false    : no persistent connections (safer in FPM/CLI contexts)
     *
     * @throws DatabaseException If the connection cannot be established.
     */
    private function __construct()
    {
        $config = require BASE_PATH . '/config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['database']
        );

        try {
            $this->connection = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
            ]);
        } catch (PDOException $e) {
            // Wrap and rethrow — never expose raw PDO messages to the client.
            throw new DatabaseException(
                'Database connection failed: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * Blocks cloning to enforce the Singleton contract.
     */
    private function __clone(): void {}

    /**
     * Blocks unserialization to prevent Singleton bypass.
     *
     * @throws RuntimeException Always.
     */
    public function __wakeup(): never
    {
        throw new RuntimeException('Cannot deserialize a singleton.');
    }

    /**
     * Returns the single Database instance (lazy initialization).
     *
     * Thread safety note: PHP-FPM processes each request in a single thread,
     * so no mutex is required here.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Exposes the underlying PDO connection.
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
