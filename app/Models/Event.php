<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Event model — data-access layer for the `events` table.
 */
class Event extends Model
{
    /**
     * Inserts a new event row, silently ignoring duplicates.
     *
     * INSERT IGNORE relies on the UNIQUE index on `event_hash`.
     * When a duplicate is skipped, lastInsertId() returns '0'.
     *
     * @param int    $customerId Customer FK.
     * @param string $eventType  Event name (e.g. "purchase").
     * @param string $occurredAt MySQL DATETIME string (UTC).
     * @param string $hash       SHA1 deduplication fingerprint.
     * @return int               Inserted event ID, or 0 on duplicate.
     */
    public function create(int $customerId, string $eventType, string $occurredAt, string $hash): int
    {
        $sql = <<<SQL
            INSERT IGNORE INTO events
                (customer_id, event_type, event_hash, occurred_at, created_at)
            VALUES
                (:customer_id, :event_type, :event_hash, :occurred_at, NOW())
        SQL;

        $this->execute($sql, [
            ':customer_id' => $customerId,
            ':event_type'  => $eventType,
            ':event_hash'  => $hash,
            ':occurred_at' => $occurredAt,
        ]);

        return (int) $this->lastInsertId();
    }

    /**
     * Returns the last N events for a customer, most recent first.
     *
     * Properties are fetched in a second query and merged in PHP to
     * avoid GROUP_CONCAT length limits on large property sets.
     *
     * @param int $customerId
     * @param int $limit      Maximum number of events to return.
     * @return array
     */
    public function findLastByCustomer(int $customerId, int $limit = 10): array
    {
        // Step 1 – fetch events (uses idx_events_customer_id)
        $sql = <<<SQL
            SELECT id, event_type, occurred_at, created_at
            FROM events
            WHERE customer_id = :customer_id
            ORDER BY occurred_at DESC
            LIMIT :limit
        SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':customer_id', $customerId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit',       $limit,      \PDO::PARAM_INT);
        $stmt->execute();

        $events = $stmt->fetchAll();

        if (empty($events)) {
            return [];
        }

        // Step 2 – fetch all properties for those event IDs in one query
        $ids          = array_column($events, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $propStmt = $this->db->prepare(
            "SELECT event_id, property_key, property_value
             FROM event_properties
             WHERE event_id IN ({$placeholders})"
        );
        $propStmt->execute($ids);
        $allProps = $propStmt->fetchAll();

        // Step 3 – index properties by event_id
        $propsByEvent = [];
        foreach ($allProps as $prop) {
            $propsByEvent[(int) $prop['event_id']][] = [
                'key'   => $prop['property_key'],
                'value' => $prop['property_value'],
            ];
        }

        // Step 4 – attach properties to each event
        foreach ($events as &$event) {
            $event['properties'] = $propsByEvent[(int) $event['id']] ?? [];
        }

        return $events;
    }

    /**
     * Returns aggregated statistics for a customer:
     *  - events_by_type : [ {event_type, count}, … ]
     *  - total_spend    : sum of numeric 'amount' properties
     *
     * @param int $customerId
     * @return array{events_by_type: array, total_spend: float}
     */
    public function getStatsByCustomer(int $customerId): array
    {
        // Event count per type — idx_events_customer_id
        $countSql = <<<SQL
            SELECT event_type, COUNT(*) AS count
            FROM events
            WHERE customer_id = :customer_id
            GROUP BY event_type
            ORDER BY count DESC
        SQL;

        $eventCounts = $this->query($countSql, [':customer_id' => $customerId])->fetchAll();

        // Total monetary spend — idx_events_customer_id + idx_ep_key_numeric
        $spendSql = <<<SQL
            SELECT COALESCE(SUM(ep.property_numeric), 0) AS total_spend
            FROM events e
            JOIN event_properties ep
                ON ep.event_id = e.id
               AND ep.property_key = 'amount'
            WHERE e.customer_id = :customer_id
        SQL;

        $totalSpend = (float) $this->query($spendSql, [':customer_id' => $customerId])->fetchColumn();

        return [
            'events_by_type' => $eventCounts,
            'total_spend'    => $totalSpend,
        ];
    }
}
