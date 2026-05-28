<?php
declare(strict_types=1);

namespace App\Core;

/**
 * HTTP response builder.
 *
 * All methods terminate execution via exit after sending the response,
 * which is reflected by the `never` return type.
 */
class Response
{
    /**
     * Sends a JSON response and terminates execution.
     *
     * @param mixed $data   Data to JSON-encode.
     * @param int   $status HTTP status code.
     */
    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        exit;
    }

    /**
     * Sends a standardised JSON error response and terminates.
     *
     * @param string $message Human-readable error description.
     * @param int    $status  HTTP status code (4xx or 5xx).
     * @param array  $errors  Optional field-level validation error detail.
     */
    public static function error(string $message, int $status = 400, array $errors = []): never
    {
        $body = ['error' => $message];

        if (!empty($errors)) {
            $body['errors'] = $errors;
        }

        self::json($body, $status);
    }

    /**
     * Sends an HTML response and terminates.
     *
     * @param string $content HTML body.
     * @param int    $status  HTTP status code.
     */
    public static function html(string $content, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        echo $content;

        exit;
    }

    /**
     * Redirects the client to a URL and terminates.
     *
     * @param string $url    Target URL.
     * @param int    $status HTTP redirect code (301 or 302).
     */
    public static function redirect(string $url, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $url);

        exit;
    }
}
