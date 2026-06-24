<?php

declare(strict_types=1);

namespace App;

/**
 * Validation ruleset for a cat card, shared by api/cats.php (bot-facing
 * create/update) -- same "collect every error, don't bail on the first one"
 * shape as RequestValidator.
 */
final class CatValidator
{
    public const COLORS = ['чорний', 'білий', 'блакитний', 'кремовий', 'лиловий', 'інший'];
    public const STATUSES = ['published', 'draft'];

    /**
     * @return string[] Validation error messages; empty means valid.
     */
    public static function validate(
        string $name,
        string $color,
        int $ageMonths,
        int $priceEur,
        string $description
    ): array {
        $errors = [];

        if (mb_strlen(trim($name)) <= 1) {
            $errors[] = "Ім'я кошеняти повинно бути більше одного символу.";
        }

        if (!in_array($color, self::COLORS, true)) {
            $errors[] = 'Невідомий колір. Дозволені: ' . implode(', ', self::COLORS) . '.';
        }

        if ($ageMonths <= 0 || $ageMonths > 240) {
            $errors[] = 'Вік (у місяцях) повинен бути додатнім і реалістичним.';
        }

        if ($priceEur <= 0 || $priceEur > 100000) {
            $errors[] = 'Ціна (у євро) повинна бути додатньою.';
        }

        if (mb_strlen(trim($description)) <= 9) {
            $errors[] = 'Опис занадто короткий (мінімум 10 символів).';
        }

        return $errors;
    }

    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::STATUSES, true);
    }
}
