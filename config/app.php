<?php
declare(strict_types=1);

use App\Core\Env;

return [
    'env'   => Env::get('APP_ENV', 'production'),
    'debug' => Env::get('APP_DEBUG', 'false') === 'true',
    'url'   => Env::get('APP_URL', 'http://localhost'),
];
