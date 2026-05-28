<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Services\EventService;

/**
 * Processes an event payload asynchronously.
 *
 * Pushed to the queue by EventController when QUEUE_ENABLED=true.
 * The worker picks it up and calls EventService::ingest().
 */
final class ProcessEventJob implements JobInterface
{
    public function __construct(private readonly array $data) {}

    public function handle(): void
    {
        (new EventService())->ingest($this->data);
    }
}
