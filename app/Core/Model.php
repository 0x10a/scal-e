<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

/**
 * Abstract base model.
 *
 * Provides a PDO instance (via the Database Singleton) and
 * common query helpers that all concrete models inherit.
 */
abstract class Model
{
    /** @var PDO The shared database connection. */
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Prepares and executes a parameterised query.
     *
     * Returns the executed PDOStatement so callers can fetch results.
     *
     * @param string $sql    SQL with named (:name) or positional (?) placeholders.
     * @param array  $params Bound parameter values.
     * @return PDOStatement
     */
    protected function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    /**
     * Executes a statement and returns the number of affected rows.
     *
     * Suitable for INSERT / UPDATE / DELETE when the result set is not needed.
     *
     * @param string $sql    Parameterised SQL.
     * @param array  $params Bound parameter values.
     * @return int           Affected row count.
     */
    protected function execute(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Returns the ID of the last inserted row.
     *
     * @return string Last insert ID as string (PDO always returns string).
     */
    protected function lastInsertId(): string
    {
        return $this->db->lastInsertId();
    }
}
