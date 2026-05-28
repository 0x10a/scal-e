<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Customer model — data-access layer for the `customers` table.
 */
class Customer extends Model
{
    /**
     * Creates a new customer or updates the name if the email already exists.
     *
     * Uses an atomic INSERT … ON DUPLICATE KEY UPDATE to avoid race conditions.
     * When a duplicate is updated, MySQL's lastInsertId() returns 0, so we
     * perform a secondary SELECT by email to retrieve the real ID.
     *
     * @param string $email Unique customer email (normalised to lowercase).
     * @param string $name  Customer display name.
     * @return int          The customer's primary key.
     */
    public function upsert(string $email, string $name): int
    {
        $sql = <<<SQL
            INSERT INTO customers (email, name, created_at, updated_at)
            VALUES (:email, :name, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                name       = VALUES(name),
                updated_at = NOW()
        SQL;

        $this->execute($sql, [':email' => $email, ':name' => $name]);

        return $this->findIdByEmail($email);
    }

    /**
     * Finds a customer by primary key.
     *
     * @param int $id Customer ID.
     * @return array|null Customer row, or null if not found.
     */
    public function findById(int $id): ?array
    {
        $sql = <<<SQL
            SELECT id, email, name, created_at, updated_at
            FROM customers
            WHERE id = :id
        SQL;

        $row = $this->query($sql, [':id' => $id])->fetch();

        return $row ?: null;
    }

    /**
     * Returns a paginated list of customers, newest first.
     *
     * PDO::PARAM_INT is required for LIMIT / OFFSET to avoid emulated
     * prepare quoting the integers as strings.
     *
     * @param int $limit  Records per page.
     * @param int $offset Row offset.
     * @return array
     */
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $sql = <<<SQL
            SELECT id, email, name, created_at, updated_at
            FROM customers
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit',  $limit,  \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Returns the total number of customers.
     */
    public function count(): int
    {
        return (int) $this->query('SELECT COUNT(*) FROM customers')->fetchColumn();
    }

    // ── Private helpers ──────────────────────────────────────────

    /**
     * Retrieves a customer ID by email (used after ON DUPLICATE KEY upsert).
     */
    private function findIdByEmail(string $email): int
    {
        $row = $this->query(
            'SELECT id FROM customers WHERE email = :email',
            [':email' => $email]
        )->fetch();

        return (int) ($row['id'] ?? 0);
    }
}
