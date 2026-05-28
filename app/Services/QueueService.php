<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use Predis\Client;

/**
 * Redis List-based queue service.
 *
 * push()  → RPUSH  (enqueue a job)
 * pop()   → BLPOP  (blocking dequeue — used by the worker)
 */
final class QueueService
{
    private const QUEUE_KEY = 'queue:jobs';

    private Client $redis;

    public function __construct()
    {
        $this->redis = new Client([
            'scheme'             => 'tcp',
            'host'               => Env::get('REDIS_HOST', '127.0.0.1'),
            'port'               => 6379,
            'read_write_timeout' => -1, // required for BLPOP (no read timeout)
        ]);
    }

    /**
     * Pushes a job onto the queue.
     *
     * @param class-string $jobClass Fully-qualified job class name.
     * @param array        $data     Serialisable payload passed to the job constructor.
     */
    public function push(string $jobClass, array $data): void
    {
        $this->redis->rpush(self::QUEUE_KEY, [json_encode(['job' => $jobClass, 'data' => $data])]);
    }

    /**
     * Blocks until a job is available, then returns its decoded payload.
     * Used exclusively by the CLI worker.
     *
     * @return array{job: string, data: array}|null
     */
    public function pop(): ?array
    {
        $result = $this->redis->blpop(self::QUEUE_KEY, 0);

        if ($result === null) {
            return null;
        }

        return json_decode($result[1], true);
    }
}
