<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

// Load test environment variables
\App\Core\Env::load(BASE_PATH . '/.env');
