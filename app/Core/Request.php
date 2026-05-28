<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Represents the current HTTP request.
 *
 * Wraps PHP superglobals and provides safe, typed accessors.
 * JSON request bodies are parsed automatically from php://input.
 */
class Request
{
    private string $method;
    private string $uri;
    private array  $queryParams;
    private array  $body;
    private array  $headers;

    public function __construct()
    {
        $this->method      = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri         = $this->parseUri();
        $this->queryParams = $_GET;
        $this->body        = $this->parseBody();
        $this->headers     = $this->parseHeaders();
    }

    /**
     * Returns the HTTP method (GET, POST, …).
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Returns the URI path without query string, trailing slash stripped.
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Returns a query string parameter, or $default if absent.
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * Returns a parsed body field, or $default if absent.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * Returns the entire parsed request body as an array.
     */
    public function all(): array
    {
        return $this->body;
    }

    /**
     * Returns a request header value (case-insensitive), or null.
     *
     * @param string $name Header name, e.g. 'Content-Type' or 'content-type'.
     */
    public function header(string $name): ?string
    {
        $normalized = strtolower(str_replace('_', '-', $name));

        return $this->headers[$normalized] ?? null;
    }

    /**
     * Returns true when the request sends or expects JSON.
     */
    public function isJson(): bool
    {
        $accept      = $this->header('accept') ?? '';
        $contentType = $this->header('content-type') ?? '';

        return str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json');
    }

    // ── Private helpers ─────────────────────────────────────────

    private function parseUri(): string
    {
        $raw  = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($raw, PHP_URL_PATH) ?? '/';

        // Normalize: strip trailing slash (except root)
        return rtrim($path, '/') ?: '/';
    }

    private function parseBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $raw     = file_get_contents('php://input');
            $decoded = json_decode($raw ?: '', true);

            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }

    private function parseHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name            = strtolower(str_replace(['HTTP_', '_'], ['', '-'], $key));
                $headers[$name]  = $value;
            }
        }

        // CONTENT_TYPE and CONTENT_LENGTH are not prefixed with HTTP_
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }

        return $headers;
    }
}
