<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Segmentation engine.
 *
 * Translates a structured conditions array into an optimised SQL query
 * using EXISTS subqueries — one per condition — and executes it.
 *
 * Each EXISTS subquery joins `events` + `event_properties` with the
 * property_key filter placed in the JOIN ON clause, so MySQL can use
 * the composite index (property_key, property_numeric) or
 * (property_key, property_value) as a single index-range scan rather
 * than two separate lookups.
 *
 * Supported operators:
 *   >  >=  <  <=          → property_numeric column (B-tree range scan)
 *   =  !=  (numeric val)  → property_numeric column
 *   =  !=  (string val)   → property_value   column
 *   contains              → property_value LIKE '%…%'
 *
 * All user-supplied values are bound as PDO parameters.
 * Operators are validated against a whitelist before use.
 *
 * Multiple conditions are combined with AND logic (one EXISTS per condition).
 */
class SegmentService
{
    /** Operators that require the DECIMAL numeric column for indexed comparisons. */
    private const NUMERIC_OPERATORS = ['>', '>=', '<', '<='];

    /** Full whitelist of allowed operators (prevents SQL injection via dynamic SQL). */
    private const ALLOWED_OPERATORS = ['=', '!=', '>', '>=', '<', '<=', 'contains'];

    private CacheService $cache;

    public function __construct()
    {
        $this->cache = new CacheService();
    }

    /**
     * Runs a segmentation query and returns matching customers.
     *
     * @param array $conditions Validated conditions from SegmentValidator::validate().
     * @return array            Matching customers: [{id, email, name, created_at}, …]
     */
    public function query(array $conditions): array
    {
        $cacheKey = 'segment_' . sha1(json_encode($conditions));

        return $this->cache->remember($cacheKey, 30, function () use ($conditions) {
            if (empty($conditions)) {
                $db = Database::getInstance()->getConnection();
                return $db->query(
                    'SELECT id, email, name, created_at FROM customers ORDER BY created_at DESC'
                )->fetchAll();
            }

            [$sql, $params] = $this->buildQuery($conditions);

            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        });
    }

    /**
     * Builds the parameterised segmentation SQL.
     *
     * @param array $conditions Non-empty validated conditions array.
     * @return array{0: string, 1: array}  [SQL string, PDO parameter map]
     */
    private function buildQuery(array $conditions): array
    {
        $existsClauses = [];
        $params        = [];

        foreach ($conditions as $i => $condition) {
            $operator = $condition['operator'];
            $value    = $condition['value'];

            $keyParam   = ":key_{$i}";
            $eventParam = ":event_{$i}";
            $valParam   = ":val_{$i}";

            $params[$keyParam]   = $condition['property'];
            $params[$eventParam] = $condition['event'];

            [$valueColumn, $sqlOperator, $boundValue] = $this->resolveOperator($operator, $value);

            $params[$valParam] = $boundValue;

            // The property_key filter is in the JOIN ON clause — this lets MySQL use
            // the composite index prefix before applying the value condition.
            $existsClauses[] = <<<SQL
                EXISTS (
                    SELECT 1
                    FROM events e_{$i}
                    JOIN event_properties ep_{$i}
                        ON ep_{$i}.event_id     = e_{$i}.id
                       AND ep_{$i}.property_key = {$keyParam}
                    WHERE e_{$i}.customer_id = c.id
                      AND e_{$i}.event_type  = {$eventParam}
                      AND ep_{$i}.{$valueColumn} {$sqlOperator} {$valParam}
                )
            SQL;
        }

        $whereClause = implode("\nAND ", $existsClauses);

        $sql = <<<SQL
            SELECT DISTINCT c.id, c.email, c.name, c.created_at
            FROM customers c
            WHERE {$whereClause}
            ORDER BY c.created_at DESC
        SQL;

        return [$sql, $params];
    }

    /**
     * Maps an operator + value to (column_name, SQL_operator, bound_value).
     *
     * @param string $operator User-supplied operator (validated against whitelist).
     * @param mixed  $value    User-supplied comparison value.
     * @return array{0: string, 1: string, 2: mixed}
     */
    private function resolveOperator(string $operator, mixed $value): array
    {
        if ($operator === 'contains') {
            return ['property_value', 'LIKE', '%' . $value . '%'];
        }

        if (in_array($operator, self::NUMERIC_OPERATORS, true)) {
            return ['property_numeric', $operator, (float) $value];
        }

        // = or != : use numeric column when the value is numeric for proper index usage
        if (is_numeric($value)) {
            return ['property_numeric', $operator, (float) $value];
        }

        return ['property_value', $operator, (string) $value];
    }
}
