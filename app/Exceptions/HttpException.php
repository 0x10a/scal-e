<?php
declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Represents an HTTP-level error (4xx / 5xx).
 *
 * Carries an HTTP status code alongside the message so that
 * the front controller can respond with the correct HTTP code.
 */
class HttpException extends RuntimeException
{
    /**
     * @param int        $statusCode HTTP status code (e.g. 400, 404, 500).
     * @param string     $message    Human-readable error description.
     * @param \Throwable|null $previous Optional chained exception.
     */
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * Returns the HTTP status code associated with this exception.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
