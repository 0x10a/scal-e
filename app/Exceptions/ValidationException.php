<?php
declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when input validation fails.
 *
 * Carries field-level error details so the API can return
 * a structured 422 Unprocessable Entity response.
 */
class ValidationException extends RuntimeException
{
    /**
     * @param array<string, string> $errors Field → error message map.
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Validation failed.');
    }

    /**
     * Returns the field-level validation errors.
     *
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
