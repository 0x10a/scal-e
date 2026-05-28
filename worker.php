#!/usr/bin/env php
<?php
declare(strict_types=1);

define('BASE_PATH', __DIR__);

require BASE_PATH . '/vendor/autoload.php';

// Load .env for local dev (Docker injects env vars directly)
App\Core\Env::load(BASE_PATH . '/.env');

use App\Services\QueueService;

echo '[worker] Started. Waiting for jobs...' . PHP_EOL;

$queue = new QueueService();

while (true) {
    $payload = $queue->pop(); // blocks until a job arrives

    if ($payload === null) {
        continue;
    }

    $jobClass = $payload['job'];
    echo "[worker] Processing: {$jobClass}" . PHP_EOL;

    try {
        /** @var \App\Jobs\JobInterface $job */
        $job = new $jobClass($payload['data']);
        $job->handle();
        echo '[worker] Done.' . PHP_EOL;
    } catch (\Throwable $e) {
        echo '[worker] Error: ' . $e->getMessage() . PHP_EOL;
    }
}
