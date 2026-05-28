<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal .env file loader.
 *
 * Parses KEY=VALUE pairs from a .env file and populates
 * both $_ENV and the process environment via putenv().
 */
class Env
{
    /**
     * Loads an .env file into the environment.
     *
     * Lines starting with '#' and blank lines are ignored.
     * Surrounding quotes are stripped from values.
     *
     * @param string $path Absolute path to the .env file.
     */
    public static function load(string $path): void
    {
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');

            $key   = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");

            if ($key === '') {
                continue;
            }

            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }

    /**
     * Returns an environment variable or a default value.
     *
     * @param string      $key     Variable name.
     * @param string|null $default Fallback when variable is absent.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        $value = getenv($key);

        return ($value !== false) ? $value : $default;
    }
}
