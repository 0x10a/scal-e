<?php
declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when a requested resource cannot be found (HTTP 404).
 */
class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Resource not found.')
    {
        parent::__construct(404, $message);
    }
}
