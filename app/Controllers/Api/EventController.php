<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Env;
use App\Core\Response;
use App\Jobs\ProcessEventJob;
use App\Services\EventService;
use App\Services\QueueService;
use App\Validators\EventValidator;

/**
 * Handles the event ingestion API endpoint.
 *
 * POST /api/events
 */
class EventController extends Controller
{
    /**
     * Validates the incoming payload, delegates to EventService, and responds.
     *
     * Returns HTTP 201 on successful ingestion, 200 if the event was a duplicate.
     */
    public function store(): never
    {
        $data = (new EventValidator())->validate($this->request->all());

        if (filter_var(Env::get('QUEUE_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN)) {
            (new QueueService())->push(ProcessEventJob::class, $data);

            Response::json(['message' => 'Event queued for processing.'], 202);
        }

        $result = (new EventService())->ingest($data);

        Response::json(
            [
                'message'      => $result['deduplicated']
                    ? 'Event already recorded (duplicate skipped).'
                    : 'Event recorded successfully.',
                'customer_id'  => $result['customer_id'],
                'event_id'     => $result['event_id'],
                'deduplicated' => $result['deduplicated'],
            ],
            $result['deduplicated'] ? 200 : 201
        );
    }
}
