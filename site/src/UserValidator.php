<?php

declare(strict_types=1);

namespace App;

/**
 * Validation ruleset for account registration -- same "collect every error"
 * shape as CatValidator, but returns i18n *keys* (not finished strings),
 * since the site is multilingual and the calling page resolves them with
 * t(). See lang/*.php 'auth.error.*'.
 */
final class UserValidator
{
    public const MIN_PASSWORD = 6;

    /**
     * @return string[] i18n error keys; empty means valid.
     */
    public static function validateRegistration(
        string $email,
        string $name,
        string $password,
        string $passwordConfirm
    ): array {
        $errors = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'auth.error.email_invalid';
        }

        if (mb_strlen(trim($name)) < 2) {
            $errors[] = 'auth.error.name_short';
        }

        if (mb_strlen($password) < self::MIN_PASSWORD) {
            $errors[] = 'auth.error.password_short';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'auth.error.password_mismatch';
        }

        return $errors;
    }
}
