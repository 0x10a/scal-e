<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Customer;
use App\Models\Event;
use App\Models\EventProperty;

/**
 * Event ingestion service.
 *
 * Orchestrates customer upsert, deduplication check, event creation,
 * and property storage inside a single database transaction.
 */
class EventService
{
    private Customer      $customerModel;
    private Event         $eventModel;
    private EventProperty $propertyModel;

    public function __construct()
    {
        $this->customerModel = new Customer();
        $this->eventModel    = new Event();
        $this->propertyModel = new EventProperty();
    }

    /**
     * Processes a validated event payload end-to-end.
     *
     * Steps:
     *  1. Upsert the customer by email (atomic INSERT … ON DUPLICATE KEY UPDATE).
     *  2. Compute a SHA1 deduplication hash from customer + event + properties + timestamp.
     *  3. Attempt to INSERT the event (INSERT IGNORE skips if hash already exists).
     *  4. If the event was genuinely new, bulk-insert its properties.
     *  5. Commit — or rollback on any error.
     *
     * @param array $data Validated & sanitised payload from EventValidator::validate().
     * @return array{customer_id: int, event_id: int|null, deduplicated: bool}
     */
    public function ingest(array $data): array
    {
        // 1 – Upsert customer (outside transaction; safe to retry)
        $customerId = $this->customerModel->upsert(
            $data['customer']['email'],
            $data['customer']['name']
        );

        // 2 – Deduplication hash
        $properties = $data['properties'] ?? [];
        ksort($properties); // Normalise key order before hashing
        $timeBucket = $this->toMySQLMinuteBucket($data['timestamp']);
        $hash = sha1($customerId . $data['event'] . json_encode($properties) . $timeBucket);

        // 3 & 4 – Insert event + properties in a transaction
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();

        try {
            $eventId      = $this->eventModel->create(
                $customerId,
                $data['event'],
                $this->toMySQLDatetime($data['timestamp']),
                $hash
            );

            $deduplicated = ($eventId === 0);

            if (!$deduplicated && !empty($properties)) {
                $this->propertyModel->bulkInsert($eventId, $properties);
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        return [
            'customer_id'  => $customerId,
            'event_id'     => $deduplicated ? null : $eventId,
            'deduplicated' => $deduplicated,
        ];
    }

    /**
     * Converts an ISO-8601 timestamp to MySQL DATETIME format (UTC).
     *
     * @param string $timestamp Any ISO-8601 string, e.g. "2026-04-10T12:00:00Z".
     * @return string           MySQL-compatible "Y-m-d H:i:s".
     */
    private function toMySQLDatetime(string $timestamp): string
    {
        $dt = new \DateTimeImmutable($timestamp, new \DateTimeZone('UTC'));

        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * Rounds an ISO-8601 timestamp down to the current minute in UTC.
     *
     * Used to keep event deduplication stable across retries that happen a few
     * seconds apart, while still allowing distinct events on different minutes.
     *
     * @param string $timestamp Any ISO-8601 string.
     * @return string           Minute bucket in "Y-m-d H:i:00" format.
     */
    private function toMySQLMinuteBucket(string $timestamp): string
    {
        $dt = new \DateTimeImmutable($timestamp, new \DateTimeZone('UTC'));

        return $dt->format('Y-m-d H:i:00');
    }
}
