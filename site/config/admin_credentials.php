<?php

declare(strict_types=1);

/**
 * config/admin_credentials.php — demo admin login for this portfolio build.
 *
 * Values come from .env (see .env.example) -- change ADMIN_USERNAME and
 * regenerate ADMIN_PASSWORD_HASH there before using this anywhere beyond
 * a local demo:
 *
 *   php -r "echo password_hash('your-new-password', PASSWORD_BCRYPT), PHP_EOL;"
 */

require_once __DIR__ . '/env.php';

load_env(__DIR__ . '/../.env');

define('ADMIN_USERNAME', env('ADMIN_USERNAME', 'admin'));
define('ADMIN_PASSWORD_HASH', env('ADMIN_PASSWORD_HASH', '$2b$10$/aHMxiyAvkalENkqc2.5xOQJTdj6ua/Mb2qJ8TIbp6RUb9yJzqQhi'));
