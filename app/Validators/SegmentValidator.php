<?php
declare(strict_types=1);

namespace App\Validators;

use App\Exceptions\ValidationException;

/**
 * Validates the POST /api/segments/query request payload.
 */
class SegmentValidator
{
    /** Allowed comparison operators (whitelist prevents SQL injection). */
    public const ALLOWED_OPERATORS = ['=', '!=', '>', '>=', '<', '<=', 'contains'];

    /**
     * Validates the raw conditions array and returns sanitised conditions.
     *
     * Each condition must have:
     *  - event    : non-empty string (event type to match)
     *  - property : non-empty string (property key to inspect)
     *  - operator : one of the ALLOWED_OPERATORS
     *  - value    : any scalar value
     *
     * @param array $data Raw decoded JSON body.
     * @return array      Validated conditions array.
     * @throws ValidationException On any rule violation.
     */
    public function validate(array $data): array
    {
        $errors = [];

        if (empty($data['conditions']) || !is_array($data['conditions'])) {
            $errors['conditions'] = 'The conditions field is required and must be a non-empty array.';
            throw new ValidationException($errors);
        }

        $validated = [];

        foreach ($data['conditions'] as $i => $condition) {
            $prefix = "conditions[{$i}]";

            if (!is_array($condition)) {
                $errors["{$prefix}"] = 'Each condition must be an object.';
                continue;
            }

            if (empty($condition['event']) || !is_string($condition['event'])) {
                $errors["{$prefix}.event"] = 'Event name is required and must be a string.';
            }

            if (empty($condition['property']) || !is_string($condition['property'])) {
                $errors["{$prefix}.property"] = 'Property key is required and must be a string.';
            }

            if (
                empty($condition['operator']) ||
                !in_array($condition['operator'], self::ALLOWED_OPERATORS, true)
            ) {
                $errors["{$prefix}.operator"] = sprintf(
                    'Operator must be one of: %s.',
                    implode(', ', self::ALLOWED_OPERATORS)
                );
            }

            if (!array_key_exists('value', $condition)) {
                $errors["{$prefix}.value"] = 'Value is required.';
            }

            if (empty($errors)) {
                $validated[] = [
                    'event'    => trim($condition['event']),
                    'property' => trim($condition['property']),
                    'operator' => $condition['operator'],
                    'value'    => $condition['value'],
                ];
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $validated;
    }
}
