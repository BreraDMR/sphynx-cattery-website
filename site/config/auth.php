<?php
/**
 * config/auth.php — minimal session-based admin gate.
 *
 * This was the single biggest gap in the original coursework: the admin
 * pages (admin_requests.php, requests.php, create/edit/delete_request.php)
 * had no access control at all and were reachable from the public nav menu.
 * This file fixes that with a deliberately simple username/password login —
 * enough to demonstrate the concept for a portfolio/learning project, not a
 * production-grade auth system (no rate limiting, password reset, etc.).
 *
 * Default credentials live in config/admin_credentials.php — change them
 * there before using this anywhere beyond a local demo.
 */

session_start();

function require_admin(): void
{
    if (empty($_SESSION['is_admin'])) {
        header('Location: login.php');
        exit;
    }
}

function is_logged_in(): bool
{
    return !empty($_SESSION['is_admin']);
}

/**
 * CSRF token helpers, used by the forms/links that change data
 * (delete_request.php in particular — see docs/report.md Editor's notes).
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(?string $token): bool
{
    return !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}
