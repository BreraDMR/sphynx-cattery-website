<?php

declare(strict_types=1);

namespace Tests;

use App\RequestValidator;
use PHPUnit\Framework\TestCase;

final class RequestValidatorTest extends TestCase
{
    public function testValidSubmissionHasNoErrors(): void
    {
        $errors = RequestValidator::validate('Анна Новак', 'anna@example.com', 'Цікавить білий сфінкс.', true);
        $this->assertSame([], $errors);
    }

    public function testShortNameIsRejected(): void
    {
        $errors = RequestValidator::validate('Ан', 'anna@example.com', 'Цікавить білий сфінкс.', true);
        $this->assertContains("Ім'я повинно бути більше двох символів.", $errors);
    }

    public function testInvalidEmailIsRejected(): void
    {
        $errors = RequestValidator::validate('Анна Новак', 'not-an-email', 'Цікавить білий сфінкс.', true);
        $this->assertContains('Email заповнено некоректно.', $errors);
    }

    public function testShortNonEmptyMessageIsRejected(): void
    {
        $errors = RequestValidator::validate('Анна Новак', 'anna@example.com', 'Привіт', true);
        $this->assertContains('Повідомлення занадто коротке.', $errors);
    }

    public function testEmptyMessageIsAllowed(): void
    {
        // api.php only rejects a message that's short AND non-empty -- an
        // empty message is allowed (the public form falls back to a default).
        $errors = RequestValidator::validate('Анна Новак', 'anna@example.com', '', true);
        $this->assertSame([], $errors);
    }

    public function testMissingConsentIsRejectedByDefault(): void
    {
        $errors = RequestValidator::validate('Анна Новак', 'anna@example.com', 'Цікавить білий сфінкс.', false);
        $this->assertContains('Потрібна згода на обробку персональних даних.', $errors);
    }

    public function testConsentCanBeMadeOptional(): void
    {
        // create_request.php: an admin logging a request on a customer's
        // behalf has no consent checkbox to check.
        $errors = RequestValidator::validate('Анна Новак', 'anna@example.com', 'Цікавить білий сфінкс.', false, requireConsent: false);
        $this->assertSame([], $errors);
    }

    public function testMultipleErrorsAreAllReported(): void
    {
        $errors = RequestValidator::validate('Ан', 'not-an-email', 'Привіт', false);
        $this->assertCount(4, $errors);
    }
}
