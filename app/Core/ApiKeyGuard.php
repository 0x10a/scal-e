<?php
declare(strict_types=1);

namespace App\Core;

/**
 * API key authentication guard.
 *
 * Checks for a valid API key on every protected route before
 * the controller is instantiated. The key is read from the
 * API_KEY environment variable.
 *
 * Accepted delivery methods (checked in order):
 *   1. HTTP header  : X-Api-Key: <key>
 *   2. Query param  : ?api_key=<key>
 *
 * Responds with 401 Unauthorized if the key is absent or incorrect.
 * Responds with 500 if API_KEY is not configured in the environment.
 */
final class ApiKeyGuard
{
    /**
     * Validates the API key on the incoming request.
     *
     * Exits via Response::error() (never returns) on failure.
     *
     * @param Request $request The current HTTP request.
     */
    public static function check(Request $request): void
    {
        $validKey = Env::get('API_KEY');

        if ($validKey === null || $validKey === '') {
            Response::error('API_KEY is not configured on this server.', 500);
        }

        $provided = $request->header('x-api-key') ?? $request->query('api_key');

        if ($provided === null || !hash_equals($validKey, $provided)) {
            Response::error('Unauthorized. A valid API key is required.', 401);
        }
    }
}
