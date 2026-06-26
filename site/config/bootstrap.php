<?php

declare(strict_types=1);

/**
 * config/bootstrap.php — single entry point that every page-rendering script
 * pulls in (directly, or via includes/header.php). It wires together the
 * database ($pdo + .env), the session/auth helpers and the active locale, in
 * the right order, so individual pages don't each re-require the same three
 * files in a slightly different order.
 *
 * Pure API endpoints (api/*.php) deliberately do NOT use this -- they only
 * need config/db.php and don't render a localized page.
 */

require_once __DIR__ . '/db.php';   // defines $pdo, loads .env via env()
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/labels.php';
require_once __DIR__ . '/auth.php';  // starts the session

init_locale();
