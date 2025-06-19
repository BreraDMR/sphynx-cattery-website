<?php

declare(strict_types=1);

namespace App;

/**
 * One validation ruleset, used by api.php, create_request.php, and
 * edit_request.php alike. Before this, each of the three had its own
 * slightly different copy of these checks (e.g. create_request.php
 * required a 10-character message, api.php only required it if non-empty,
 * edit_request.php didn't check message length at all).
 */
final class RequestValidator
{
    /**
     * @return string[] Validation error messages; empty means valid.
     */
    public static function validate(
        string $name,
        string $email,
        string $message,
        bool $consent,
        bool $requireConsent = true
    ): array {
        $errors = [];

        if (mb_strlen(trim($name)) <= 2) {
            $errors[] = "Ім'я повинно бути більше двох символів.";
        }

        if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email заповнено некоректно.';
        }

        $trimmedMessage = trim($message);
        if ($trimmedMessage !== '' && mb_strlen($trimmedMessage) <= 10) {
            $errors[] = 'Повідомлення занадто коротке.';
        }

        if ($requireConsent && !$consent) {
            $errors[] = 'Потрібна згода на обробку персональних даних.';
        }

        return $errors;
    }
}
