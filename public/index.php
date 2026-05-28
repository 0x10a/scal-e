<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

// Load environment variables from .env
\App\Core\Env::load(BASE_PATH . '/.env');

// Build the HTTP request object
$request = new \App\Core\Request();

// Instantiate router and register routes
$router = new \App\Core\Router();
require BASE_PATH . '/config/routes.php';

// Dispatch — all exceptions are caught and turned into proper HTTP responses
try {
    $router->dispatch($request);
} catch (\App\Exceptions\ValidationException $e) {
    \App\Core\Response::error('Validation failed.', 422, $e->getErrors());
} catch (\App\Exceptions\NotFoundException $e) {
    \App\Core\Response::error($e->getMessage(), 404);
} catch (\App\Exceptions\HttpException $e) {
    \App\Core\Response::error($e->getMessage(), $e->getStatusCode());
} catch (\App\Exceptions\DatabaseException $e) {
    error_log('[DB] ' . $e->getMessage());
    \App\Core\Response::error('A database error occurred. Please try again later.', 500);
} catch (\Throwable $e) {
    error_log('[ERROR] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    \App\Core\Response::error('An unexpected error occurred.', 500);
}
