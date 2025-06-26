<?php

declare(strict_types=1);

/**
 * config/oauth.php — OAuth 2.0 provider definitions (Google + GitHub).
 *
 * Real authorization-code flow, no third-party SDK -- the handful of HTTP
 * calls live in oauth.php. A provider is "enabled" only when its client id +
 * secret are present in the environment, so the site runs perfectly on
 * email/password alone until those are configured (see site.env.example).
 *
 * Redirect URIs must be registered with the provider exactly. Because the
 * public URL must be stable for that, set SITE_BASE_URL (e.g. the Cloudflare
 * named-tunnel hostname); for local development it falls back to the current
 * request's scheme+host, which works with Google's allowed http://localhost.
 */

function oauth_base_url(): string
{
    $configured = env('SITE_BASE_URL');
    if ($configured) {
        return rtrim($configured, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    // Honour the proxy header the Cloudflare tunnel / Caddy set, falling back
    // to the direct Host.
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host;
}

function oauth_redirect_uri(string $provider): string
{
    return oauth_base_url() . '/oauth.php?provider=' . urlencode($provider) . '&action=callback';
}

/**
 * @return array<string, array<string, mixed>> Only providers that are fully
 *         configured (client id + secret present).
 */
function oauth_providers(): array
{
    $all = [
        'google' => [
            'label' => 'Google',
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'userinfo_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
            'scope' => 'openid email profile',
        ],
        'github' => [
            'label' => 'GitHub',
            'client_id' => env('GITHUB_CLIENT_ID'),
            'client_secret' => env('GITHUB_CLIENT_SECRET'),
            'authorize_url' => 'https://github.com/login/oauth/authorize',
            'token_url' => 'https://github.com/login/oauth/access_token',
            'userinfo_url' => 'https://api.github.com/user',
            'emails_url' => 'https://api.github.com/user/emails',
            'scope' => 'read:user user:email',
        ],
    ];

    return array_filter($all, static fn (array $p): bool => !empty($p['client_id']) && !empty($p['client_secret']));
}

function oauth_provider_config(string $provider): ?array
{
    return oauth_providers()[$provider] ?? null;
}
