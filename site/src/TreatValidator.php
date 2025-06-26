<?php

declare(strict_types=1);

namespace App;

/**
 * Validation ruleset for a treat card, shared by api/treats.php (bot-facing
 * create) -- same shape as CatValidator. CATEGORIES are canonical keys kept
 * in sync by hand with the Telegram bot's list (treats_api / bot.py) and the
 * UI labels in lang/*.php ('treats.cat.*').
 */
final class TreatValidator
{
    public const CATEGORIES = ['snacks', 'food', 'vitamins', 'toys', 'care'];
    public const STATUSES = ['published', 'draft'];

    /**
     * @return string[] Validation error messages; empty means valid.
     */
    public static function validate(
        string $name,
        string $category,
        int $priceEur,
        int $weightG,
        string $description
    ): array {
        $errors = [];

        if (mb_strlen(trim($name)) <= 1) {
            $errors[] = 'Treat name must be longer than one character.';
        }

        if (!in_array($category, self::CATEGORIES, true)) {
            $errors[] = 'Unknown category. Allowed: ' . implode(', ', self::CATEGORIES) . '.';
        }

        if ($priceEur <= 0 || $priceEur > 100000) {
            $errors[] = 'Price (in euro) must be a positive number.';
        }

        if ($weightG < 0 || $weightG > 100000) {
            $errors[] = 'Weight (in grams) must be zero or a realistic positive number.';
        }

        if (mb_strlen(trim($description)) <= 9) {
            $errors[] = 'Description is too short (minimum 10 characters).';
        }

        return $errors;
    }

    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::STATUSES, true);
    }
}
