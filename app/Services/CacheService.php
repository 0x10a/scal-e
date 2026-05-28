<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use Predis\Client;

final class CacheService
{
    private Client $redis;

    public function __construct()
    {
        $this->redis = new Client([
            'scheme' => 'tcp',
            'host'   => Env::get('REDIS_HOST', '127.0.0.1'),
            'port'   => 6379,
        ]);
    }

    /**
     * Returns cached value, or executes $callback, caches and returns its result.
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $cached = $this->redis->get($key);
        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $value = $callback();
        $this->redis->setex($key, $ttl, json_encode($value));

        return $value;
    }

    /**
     * Deletes a cached entry.
     */
    public function forget(string $key): void
    {
        $this->redis->del([$key]);
    }
}
