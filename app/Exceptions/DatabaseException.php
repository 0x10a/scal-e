<?php
declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Wraps PDO / database-level errors.
 *
 * Separating database errors from generic RuntimeException allows
 * the front controller to log them and respond with a 500 without
 * leaking internal DB details to the client.
 */
class DatabaseException extends RuntimeException {}
