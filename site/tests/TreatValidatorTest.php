<?php

declare(strict_types=1);

namespace Tests;

use App\TreatValidator;
use PHPUnit\Framework\TestCase;

final class TreatValidatorTest extends TestCase
{
    public function testValidTreatPassesWithNoErrors(): void
    {
        $errors = TreatValidator::validate('Chicken Jerky Bites', 'snacks', 6, 80, 'High-protein chicken bites.');

        $this->assertSame([], $errors);
    }

    public function testRejectsUnknownCategory(): void
    {
        $errors = TreatValidator::validate('Mystery', 'gadgets', 6, 80, 'A valid-length description here.');

        $this->assertNotEmpty($errors);
    }

    public function testRejectsShortNameAndDescriptionAndBadPrice(): void
    {
        $errors = TreatValidator::validate('X', 'snacks', 0, 80, 'short');

        // short name + non-positive price + short description = 3 errors
        $this->assertCount(3, $errors);
    }

    public function testWeightMayBeZero(): void
    {
        $errors = TreatValidator::validate('Feather Wand', 'toys', 8, 0, 'An interactive feather wand toy.');

        $this->assertSame([], $errors);
    }

    public function testStatusValidation(): void
    {
        $this->assertTrue(TreatValidator::isValidStatus('published'));
        $this->assertTrue(TreatValidator::isValidStatus('draft'));
        $this->assertFalse(TreatValidator::isValidStatus('archived'));
    }
}
