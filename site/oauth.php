<?php

declare(strict_types=1);

use App\UserRepository;

/**
 * oauth.php — OAuth 2.0 authorization-code handler for Google and GitHub.
 *
 *   oauth.php?provider=google&action=start    → redirect user to the provider
 *   oauth.php?provider=google&action=callback → exchange code, sign the user in
 *
 * No SDK: the three HTTP calls (token exchange + userinfo, + GitHub's email
 * endpoint) are plain cURL. A `state` value carried in the session guards the
 * callback against CSRF. See config/oauth.php for provider/credential config.
 */

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/oauth.php';

$provider = (string) ($_GET['provider'] ?? '');
$action = (string) ($_GET['action'] ?? '');
$cfg = oauth_provider_config($provider);

function oauth_fail(string $next = 'login.php'): never
{
    $_SESSION['flash_error'] = t('auth.error.oauth');
    header('Location: ' . $next);
    exit;
}

if ($cfg === null) {
    oauth_fail();
}

/**
 * @param array<string,string> $headers
 * @return array{code:int,body:string}
 */
function oauth_http(string $method, string $url, array $headers = [], ?string $body = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => array_merge(['User-Agent: sphynx-cattery-website'], $headers),
        CURLOPT_TIMEOUT => 20,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $code, 'body' => is_string($resp) ? $resp : ''];
}

if ($action === 'start') {
    $next = (string) ($_GET['next'] ?? 'account.php');
    if (preg_match('#^https?://#i', $next) || str_starts_with($next, '//')) {
        $next = 'account.php';
    }

    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    $_SESSION['oauth_provider'] = $provider;
    $_SESSION['oauth_next'] = $next;

    $params = http_build_query([
        'client_id' => $cfg['client_id'],
        'redirect_uri' => oauth_redirect_uri($provider),
        'response_type' => 'code',
        'scope' => $cfg['scope'],
        'state' => $state,
    ]);

    header('Location: ' . $cfg['authorize_url'] . '?' . $params);
    exit;
}

if ($action === 'callback') {
    $next = (string) ($_SESSION['oauth_next'] ?? 'account.php');

    // CSRF: the state we issued must come back, and the provider must match.
    if (
        empty($_GET['state']) || empty($_SESSION['oauth_state'])
        || !hash_equals($_SESSION['oauth_state'], (string) $_GET['state'])
        || ($_SESSION['oauth_provider'] ?? '') !== $provider
        || empty($_GET['code'])
    ) {
        oauth_fail($next);
    }
    unset($_SESSION['oauth_state'], $_SESSION['oauth_provider']);

    // 1) Exchange the authorization code for an access token.
    $tokenResp = oauth_http('POST', $cfg['token_url'], ['Accept: application/json'], http_build_query([
        'client_id' => $cfg['client_id'],
        'client_secret' => $cfg['client_secret'],
        'code' => (string) $_GET['code'],
        'redirect_uri' => oauth_redirect_uri($provider),
        'grant_type' => 'authorization_code',
    ]));

    $token = json_decode($tokenResp['body'], true);
    $accessToken = is_array($token) ? ($token['access_token'] ?? null) : null;
    if (!$accessToken) {
        oauth_fail($next);
    }

    // 2) Fetch the user profile.
    $userResp = oauth_http('GET', $cfg['userinfo_url'], ['Authorization: Bearer ' . $accessToken, 'Accept: application/json']);
    $profile = json_decode($userResp['body'], true);
    if (!is_array($profile)) {
        oauth_fail($next);
    }

    if ($provider === 'google') {
        $oauthId = (string) ($profile['sub'] ?? '');
        $email = (string) ($profile['email'] ?? '');
        $name = (string) ($profile['name'] ?? ($email !== '' ? strstr($email, '@', true) : 'Google user'));
    } else { // github
        $oauthId = (string) ($profile['id'] ?? '');
        $name = (string) ($profile['name'] ?? ($profile['login'] ?? 'GitHub user'));
        $email = (string) ($profile['email'] ?? '');
        if ($email === '' && !empty($cfg['emails_url'])) {
            $emailsResp = oauth_http('GET', $cfg['emails_url'], ['Authorization: Bearer ' . $accessToken, 'Accept: application/json']);
            $emails = json_decode($emailsResp['body'], true);
            if (is_array($emails)) {
                foreach ($emails as $e) {
                    if (!empty($e['primary']) && !empty($e['verified'])) {
                        $email = (string) $e['email'];
                        break;
                    }
                }
            }
        }
    }

    if ($oauthId === '' || $email === '') {
        oauth_fail($next);
    }

    // 3) Find-or-create the local account, linking by email when possible.
    $repo = new UserRepository($pdo);
    $user = $repo->findByOauth($provider, $oauthId);
    if ($user === null) {
        $existing = $repo->findByEmail($email);
        if ($existing !== null) {
            $repo->linkOauth($existing->id, $provider, $oauthId);
            $user = $repo->find($existing->id);
        } else {
            $user = $repo->create($email, null, $name, 'user', $provider, $oauthId);
        }
    }

    login_user($user);
    header('Location: ' . $next);
    exit;
}

oauth_fail();
