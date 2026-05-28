<?php
declare(strict_types=1);

use App\Core\Env;

return [
    'host'     => Env::get('DB_HOST', '127.0.0.1'),
    'port'     => Env::get('DB_PORT', '3306'),
    'database' => Env::get('DB_DATABASE', 'scal_e_db'),
    'username' => Env::get('DB_USERNAME', 'scal_e_user'),
    'password' => Env::get('DB_PASSWORD', ''),
];
