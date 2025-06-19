<?php

declare(strict_types=1);

/**
 * Tiny .env loader -- intentionally not a Composer dependency. The format
 * needed here (KEY=VALUE per line, # comments) is small enough that pulling
 * in vlucas/phpdotenv for it would be more dependency than the job needs;
 * this is the same thing in about a dozen lines.
 */
function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key !== '' && getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

/**
 * env('DB_HOST', 'localhost') -- getenv() with a fallback, since not every
 * environment will have a .env file (e.g. a fresh clone before running
 * `cp .env.example .env`).
 */
function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);

    return $value !== false ? $value : $default;
}
