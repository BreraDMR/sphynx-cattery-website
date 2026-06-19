<?php

declare(strict_types=1);

namespace Tests;

use App\RequestStatus;
use PHPUnit\Framework\TestCase;

final class RequestStatusTest extends TestCase
{
    public function testAllReturnsThreeStatuses(): void
    {
        $this->assertSame(['new', 'in_progress', 'closed'], RequestStatus::all());
    }

    public function testIsValidAcceptsKnownStatuses(): void
    {
        foreach (RequestStatus::all() as $status) {
            $this->assertTrue(RequestStatus::isValid($status));
        }
    }

    public function testIsValidRejectsUnknownStatus(): void
    {
        $this->assertFalse(RequestStatus::isValid('archived'));
    }

    public function testUkrainianLabelsAreHumanReadable(): void
    {
        $this->assertSame('Нова', RequestStatus::ukrainianLabel(RequestStatus::NEW));
        $this->assertSame('В роботі', RequestStatus::ukrainianLabel(RequestStatus::IN_PROGRESS));
        $this->assertSame('Закрита', RequestStatus::ukrainianLabel(RequestStatus::CLOSED));
    }

    public function testUkrainianLabelFallsBackToTheRawValue(): void
    {
        $this->assertSame('archived', RequestStatus::ukrainianLabel('archived'));
    }
}
