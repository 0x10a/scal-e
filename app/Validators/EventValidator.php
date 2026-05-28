<?php
declare(strict_types=1);

namespace App\Validators;

use App\Exceptions\ValidationException;

/**
 * Validates the POST /api/events request payload.
 */
class EventValidator
{
    /**
     * Validates the raw input and returns a sanitised data array.
     *
     * Validation rules:
     *  - customer        : required object
     *  - customer.email  : required, valid RFC 5321 address
     *  - customer.name   : required, non-empty string
     *  - event           : required string, max 100 characters
     *  - properties      : optional associative object
     *  - timestamp       : required, parseable ISO-8601 datetime
     *
     * @param array $data Raw decoded JSON body.
     * @return array      Sanitised payload.
     * @throws ValidationException On any rule violation.
     */
    public function validate(array $data): array
    {
        $errors = [];

        // ── customer ────────────────────────────────────────────
        if (empty($data['customer']) || !is_array($data['customer'])) {
            $errors['customer'] = 'The customer field is required and must be an object.';
        } else {
            if (
                empty($data['customer']['email']) ||
                !filter_var($data['customer']['email'], FILTER_VALIDATE_EMAIL)
            ) {
                $errors['customer.email'] = 'A valid email address is required.';
            }

            if (empty($data['customer']['name']) || !is_string($data['customer']['name'])) {
                $errors['customer.name'] = 'The customer name is required.';
            }
        }

        // ── event ────────────────────────────────────────────────
        if (empty($data['event']) || !is_string($data['event'])) {
            $errors['event'] = 'The event field is required and must be a string.';
        } elseif (strlen($data['event']) > 100) {
            $errors['event'] = 'The event name must not exceed 100 characters.';
        }

        // ── properties (optional) ────────────────────────────────
        if (isset($data['properties']) && !is_array($data['properties'])) {
            $errors['properties'] = 'Properties must be an object (key/value pairs).';
        }

        // ── timestamp ────────────────────────────────────────────
        if (empty($data['timestamp'])) {
            $errors['timestamp'] = 'The timestamp field is required.';
        } else {
            try {
                new \DateTimeImmutable((string) $data['timestamp']);
            } catch (\Exception) {
                $errors['timestamp'] = 'The timestamp must be a valid ISO-8601 datetime string.';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return [
            'customer'   => [
                'email' => strtolower(trim((string) $data['customer']['email'])),
                'name'  => trim((string) $data['customer']['name']),
            ],
            'event'      => trim($data['event']),
            'properties' => $data['properties'] ?? [],
            'timestamp'  => (string) $data['timestamp'],
        ];
    }
}
