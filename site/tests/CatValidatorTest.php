<?php

declare(strict_types=1);

namespace Tests;

use App\CatValidator;
use PHPUnit\Framework\TestCase;

final class CatValidatorTest extends TestCase
{
    public function testValidCatHasNoErrors(): void
    {
        $errors = CatValidator::validate('Макс', 'чорний', 3, 1450, 'Грайливе та контактне кошеня.');
        $this->assertSame([], $errors);
    }

    public function testTooShortNameIsRejected(): void
    {
        $errors = CatValidator::validate('М', 'чорний', 3, 1450, 'Грайливе та контактне кошеня.');
        $this->assertContains("Ім'я кошеняти повинно бути більше одного символу.", $errors);
    }

    public function testUnknownColorIsRejected(): void
    {
        $errors = CatValidator::validate('Макс', 'фіолетовий', 3, 1450, 'Грайливе та контактне кошеня.');
        $this->assertNotEmpty(array_filter($errors, static fn ($e) => str_contains($e, 'колір')));
    }

    public function testNonPositiveAgeIsRejected(): void
    {
        $errors = CatValidator::validate('Макс', 'чорний', 0, 1450, 'Грайливе та контактне кошеня.');
        $this->assertNotEmpty($errors);
    }

    public function testNonPositivePriceIsRejected(): void
    {
        $errors = CatValidator::validate('Макс', 'чорний', 3, 0, 'Грайливе та контактне кошеня.');
        $this->assertNotEmpty($errors);
    }

    public function testTooShortDescriptionIsRejected(): void
    {
        $errors = CatValidator::validate('Макс', 'чорний', 3, 1450, 'Короткий.');
        $this->assertContains('Опис занадто короткий (мінімум 10 символів).', $errors);
    }

    public function testMultipleErrorsAreAllReported(): void
    {
        $errors = CatValidator::validate('М', 'фіолетовий', -1, -1, 'Ні.');
        $this->assertCount(5, $errors);
    }

    public function testIsValidStatusAcceptsOnlyKnownValues(): void
    {
        $this->assertTrue(CatValidator::isValidStatus('published'));
        $this->assertTrue(CatValidator::isValidStatus('draft'));
        $this->assertFalse(CatValidator::isValidStatus('archived'));
    }
}
