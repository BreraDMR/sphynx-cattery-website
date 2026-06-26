<?php

declare(strict_types=1);

namespace Tests;

use App\UserValidator;
use PHPUnit\Framework\TestCase;

final class UserValidatorTest extends TestCase
{
    public function testValidRegistrationPasses(): void
    {
        $errors = UserValidator::validateRegistration('user@example.com', 'Alice', 'secret123', 'secret123');

        $this->assertSame([], $errors);
    }

    public function testInvalidEmailIsRejected(): void
    {
        $errors = UserValidator::validateRegistration('not-an-email', 'Alice', 'secret123', 'secret123');

        $this->assertContains('auth.error.email_invalid', $errors);
    }

    public function testShortPasswordAndMismatchAreReported(): void
    {
        $errors = UserValidator::validateRegistration('user@example.com', 'Alice', '123', '456');

        $this->assertContains('auth.error.password_short', $errors);
        $this->assertContains('auth.error.password_mismatch', $errors);
    }

    public function testShortNameIsRejected(): void
    {
        $errors = UserValidator::validateRegistration('user@example.com', 'A', 'secret123', 'secret123');

        $this->assertContains('auth.error.name_short', $errors);
    }
}
