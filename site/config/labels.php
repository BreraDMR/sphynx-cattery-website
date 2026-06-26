<?php

declare(strict_types=1);

/**
 * config/labels.php — translate the catalog's locale-independent canonical
 * values (cat colours, treat categories, request statuses) into the active
 * locale. The stored value stays stable (e.g. cats store the colour as
 * 'чорний'); only what the visitor sees is translated. See lang/*.php.
 */

/** Canonical cat colour (as stored in `cats.color`) → i18n key. */
const CAT_COLOR_KEYS = [
    'чорний' => 'color.black',
    'білий' => 'color.white',
    'блакитний' => 'color.blue',
    'кремовий' => 'color.cream',
    'лиловий' => 'color.lilac',
    'інший' => 'color.other',
];

/** The colours offered as filter buttons on the catalog (value => i18n key). */
const CAT_FILTER_COLORS = [
    'чорний' => 'color.black',
    'білий' => 'color.white',
    'блакитний' => 'color.blue',
    'інший' => 'color.other',
];

function cat_color_label(string $canonical): string
{
    $key = CAT_COLOR_KEYS[$canonical] ?? null;

    return $key !== null ? t($key) : $canonical;
}

/** Treat category canonical key → i18n key (see TreatValidator::CATEGORIES). */
const TREAT_CATEGORY_KEYS = [
    'snacks' => 'treats.cat.snacks',
    'food' => 'treats.cat.food',
    'vitamins' => 'treats.cat.vitamins',
    'toys' => 'treats.cat.toys',
    'care' => 'treats.cat.care',
];

function treat_category_label(string $canonical): string
{
    $key = TREAT_CATEGORY_KEYS[$canonical] ?? null;

    return $key !== null ? t($key) : $canonical;
}

function request_status_label(string $status): string
{
    return t('status.' . $status);
}
