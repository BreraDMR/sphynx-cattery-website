<?php

declare(strict_types=1);

/**
 * config/i18n.php — tiny server-side i18n layer.
 *
 * The site ships in three languages: English (default/source), Czech and
 * Ukrainian. Translations live in lang/<locale>.php as flat dot-key arrays
 * ('nav.catalog' => '...'). Dynamic catalog data (cat/treat names and
 * descriptions entered by the admin) is intentionally NOT translated per
 * locale -- only the UI chrome is, which is the usual shape for a catalog.
 *
 * Locale resolution order (init_locale): ?lang= query → `locale` cookie →
 * default 'en'. The chosen locale is persisted in a cookie + the session so
 * the choice sticks across pages and visits.
 */

const SUPPORTED_LOCALES = ['en', 'cs', 'uk'];
const DEFAULT_LOCALE = 'en';

/** @var array<string,string>|null Loaded dictionary for the active locale. */
$GLOBALS['__i18n_dict'] = null;
/** @var array<string,string>|null English fallback dictionary. */
$GLOBALS['__i18n_fallback'] = null;
$GLOBALS['__i18n_locale'] = DEFAULT_LOCALE;

/**
 * @return array<string,string>
 */
function load_locale_dict(string $locale): array
{
    $path = __DIR__ . '/../lang/' . $locale . '.php';
    if (!is_file($path)) {
        return [];
    }
    /** @var array<string,string> $dict */
    $dict = require $path;

    return is_array($dict) ? $dict : [];
}

/**
 * Resolves and stores the active locale. Safe to call once per request,
 * before any output (it may set a cookie). Must run after session_start().
 */
function init_locale(): void
{
    $locale = DEFAULT_LOCALE;

    if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LOCALES, true)) {
        $locale = (string) $_GET['lang'];
        setcookie('locale', $locale, [
            'expires' => time() + 60 * 60 * 24 * 365,
            'path' => '/',
            'samesite' => 'Lax',
        ]);
        $_SESSION['locale'] = $locale;
    } elseif (!empty($_SESSION['locale']) && in_array($_SESSION['locale'], SUPPORTED_LOCALES, true)) {
        $locale = (string) $_SESSION['locale'];
    } elseif (isset($_COOKIE['locale']) && in_array($_COOKIE['locale'], SUPPORTED_LOCALES, true)) {
        $locale = (string) $_COOKIE['locale'];
        $_SESSION['locale'] = $locale;
    }

    $GLOBALS['__i18n_locale'] = $locale;
    $GLOBALS['__i18n_dict'] = load_locale_dict($locale);
    $GLOBALS['__i18n_fallback'] = $locale === DEFAULT_LOCALE
        ? $GLOBALS['__i18n_dict']
        : load_locale_dict(DEFAULT_LOCALE);
}

function current_locale(): string
{
    return $GLOBALS['__i18n_locale'] ?? DEFAULT_LOCALE;
}

/**
 * Translate a key. Falls back to English, then to the key itself, so a
 * missing translation degrades visibly-but-safely rather than blowing up.
 * Supports {placeholder} interpolation: t('cart.total', ['sum' => 42]).
 *
 * @param array<string,string|int> $params
 */
function t(string $key, array $params = []): string
{
    $dict = $GLOBALS['__i18n_dict'] ?? [];
    $fallback = $GLOBALS['__i18n_fallback'] ?? [];

    $value = $dict[$key] ?? $fallback[$key] ?? $key;

    if ($params !== []) {
        foreach ($params as $name => $replacement) {
            $value = str_replace('{' . $name . '}', (string) $replacement, $value);
        }
    }

    return $value;
}

/** Escaped translate -- convenience for echoing straight into HTML. */
function te(string $key, array $params = []): string
{
    return htmlspecialchars(t($key, $params), ENT_QUOTES, 'UTF-8');
}

/**
 * Build a URL to the current page with ?lang=<locale> added/replaced --
 * used by the header language switcher so switching keeps you on the page.
 */
function lang_switch_url(string $locale): string
{
    $params = $_GET;
    $params['lang'] = $locale;
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

    return $path . '?' . http_build_query($params);
}

/**
 * Native-language label for a locale code, for the switcher UI.
 */
function locale_label(string $locale): string
{
    return match ($locale) {
        'en' => 'EN',
        'cs' => 'CS',
        'uk' => 'UK',
        default => strtoupper($locale),
    };
}
