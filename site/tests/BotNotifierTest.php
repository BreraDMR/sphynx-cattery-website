<?php

declare(strict_types=1);

namespace Tests;

use App\BotNotifier;
use App\RequestRecord;
use PHPUnit\Framework\TestCase;

final class BotNotifierTest extends TestCase
{
    public function testNoOpWhenNotifyUrlIsNotConfigured(): void
    {
        // NOTIFY_URL is unset in the test environment -- this must not
        // throw or attempt a network call, just silently do nothing.
        $request = RequestRecord::fromRow([
            'id' => 1,
            'name' => 'Тест',
            'email' => 'test@example.com',
            'phone' => null,
            'age' => null,
            'color' => null,
            'message' => 'Перевірка no-op шляху.',
            'consent' => 1,
            'status' => 'new',
            'created_at' => '2026-01-01 00:00:00',
        ]);

        BotNotifier::notifyNewRequest($request);
        $this->addToAssertionCount(1);
    }
}
