<?php

declare(strict_types=1);

use App\UserRecord;
use App\UserRepository;

/**
 * config/auth.php — session-based authentication with user accounts + roles.
 *
 * The original coursework had a single hard-coded admin login and no concept
 * of site users at all. This now backs a real `users` table (see
 * src/UserRepository): visitors can register / log in (email+password or an
 * OAuth provider), the admin pages are gated by role='admin', and the cart
 * belongs to a logged-in user.
 *
 * Auth state in the session is just the user id; the row is loaded lazily
 * via current_user() so a deleted/demoted account stops working immediately.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** @var UserRecord|null|false false = not yet looked up this request. */
$GLOBALS['__current_user'] = false;

function current_user(): ?UserRecord
{
    if ($GLOBALS['__current_user'] !== false) {
        return $GLOBALS['__current_user'];
    }

    $user = null;
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId !== null && isset($GLOBALS['pdo'])) {
        $repo = new UserRepository($GLOBALS['pdo']);
        $user = $repo->find((int) $userId);
    }

    $GLOBALS['__current_user'] = $user;

    return $user;
}

function login_user(UserRecord $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user->id;
    $GLOBALS['__current_user'] = $user;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    $GLOBALS['__current_user'] = null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();

    return $user !== null && $user->role === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? 'account.php'));
        exit;
    }
}

function require_admin(): void
{
    if (!is_admin()) {
        // Non-admins (and guests) are bounced to the login page rather than
        // shown a bare 403 -- the admin pages aren't even linked for them.
        header('Location: login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? 'admin_requests.php'));
        exit;
    }
}

/**
 * CSRF token helpers, used by the forms/links that change data
 * (delete_request.php, cart actions, login/register -- everything stateful).
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
