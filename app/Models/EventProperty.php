<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * EventProperty model — data-access layer for the `event_properties` table.
 */
class EventProperty extends Model
{
    /**
     * Bulk-inserts all properties for a given event.
     *
     * For each property, the raw string value is stored in `property_value`.
     * If the value is numeric, it is also stored as a DECIMAL in `property_numeric`,
     * enabling the composite index (property_key, property_numeric) to serve
     * range comparisons in the segmentation engine without CAST operations.
     *
     * @param int   $eventId    Parent event ID.
     * @param array $properties Associative array: property key → raw value.
     */
    public function bulkInsert(int $eventId, array $properties): void
    {
        if (empty($properties)) {
            return;
        }

        $sql = <<<SQL
            INSERT INTO event_properties
                (event_id, property_key, property_value, property_numeric)
            VALUES
                (:event_id, :key, :value, :numeric)
        SQL;

        $stmt = $this->db->prepare($sql);

        foreach ($properties as $key => $value) {
            $stringValue  = (string) $value;
            $numericValue = is_numeric($value) ? (float) $value : null;

            $stmt->execute([
                ':event_id' => $eventId,
                ':key'      => (string) $key,
                ':value'    => $stringValue,
                ':numeric'  => $numericValue,
            ]);
        }
    }
}
