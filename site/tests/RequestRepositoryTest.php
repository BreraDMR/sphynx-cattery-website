<?php

declare(strict_types=1);

namespace Tests;

use App\RequestRepository;
use App\RequestStatus;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Runs against an in-memory SQLite database rather than MySQL, so these
 * tests don't need a real database server -- the SQL in RequestRepository
 * is plain ANSI-ish SQL (no MySQL-specific syntax), so it runs unchanged
 * against either driver.
 */
final class RequestRepositoryTest extends TestCase
{
    private PDO $pdo;
    private RequestRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            'CREATE TABLE requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                phone TEXT NULL,
                age TEXT NULL,
                color TEXT NULL,
                message TEXT NOT NULL,
                consent INTEGER NOT NULL DEFAULT 0,
                status TEXT NOT NULL DEFAULT "new",
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $this->repo = new RequestRepository($this->pdo);
    }

    public function testCreateReturnsANewId(): void
    {
        $id = $this->repo->create('Анна Новак', 'anna@example.com', '+420700000000', '2-3', 'білий', 'Цікавить білий сфінкс.', true);
        $this->assertSame(1, $id);
    }

    public function testCreatedRequestStartsWithStatusNew(): void
    {
        $id = $this->repo->create('Анна Новак', 'anna@example.com', null, null, null, 'Цікавить білий сфінкс.', true);
        $record = $this->repo->find($id);

        $this->assertNotNull($record);
        $this->assertSame(RequestStatus::NEW, $record->status);
        $this->assertTrue($record->consent);
    }

    public function testFindReturnsNullForMissingId(): void
    {
        $this->assertNull($this->repo->find(999));
    }

    public function testUpdateChangesNameEmailPhoneAndMessage(): void
    {
        $id = $this->repo->create('Анна Новак', 'anna@example.com', null, null, null, 'Цікавить білий сфінкс.', true);

        $this->repo->update($id, 'Анна Іванова', 'iванова@example.com', '+420700000001', 'Оновлене повідомлення.');
        $record = $this->repo->find($id);

        $this->assertSame('Анна Іванова', $record->name);
        $this->assertSame('+420700000001', $record->phone);
        $this->assertSame('Оновлене повідомлення.', $record->message);
    }

    public function testUpdateStatusChangesOnlyStatus(): void
    {
        $id = $this->repo->create('Анна Новак', 'anna@example.com', null, null, null, 'Цікавить білий сфінкс.', true);

        $this->repo->updateStatus($id, RequestStatus::CLOSED);
        $record = $this->repo->find($id);

        $this->assertSame(RequestStatus::CLOSED, $record->status);
        $this->assertSame('Анна Новак', $record->name);
    }

    public function testDeleteRemovesTheRow(): void
    {
        $id = $this->repo->create('Анна Новак', 'anna@example.com', null, null, null, 'Цікавить білий сфінкс.', true);

        $this->repo->delete($id);

        $this->assertNull($this->repo->find($id));
    }

    public function testAllOrdersByIdDescending(): void
    {
        $first = $this->repo->create('Перша', 'first@example.com', null, null, null, 'Перше повідомлення.', true);
        $second = $this->repo->create('Друга', 'second@example.com', null, null, null, 'Друге повідомлення.', true);

        $all = $this->repo->all();

        $this->assertSame($second, $all[0]->id);
        $this->assertSame($first, $all[1]->id);
    }

    public function testAllFiltersByStatus(): void
    {
        $newId = $this->repo->create('Нова', 'new@example.com', null, null, null, 'Повідомлення нової заявки.', true);
        $closedId = $this->repo->create('Закрита', 'closed@example.com', null, null, null, 'Повідомлення закритої заявки.', true);
        $this->repo->updateStatus($closedId, RequestStatus::CLOSED);

        $newOnly = $this->repo->all(RequestStatus::NEW);

        $this->assertCount(1, $newOnly);
        $this->assertSame($newId, $newOnly[0]->id);
    }

    public function testAllRespectsLimitAndOffsetForPagination(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->repo->create("Заявка {$i}", "request{$i}@example.com", null, null, null, "Повідомлення номер {$i}.", true);
        }

        $page1 = $this->repo->all(null, 2, 0);
        $page2 = $this->repo->all(null, 2, 2);

        $this->assertCount(2, $page1);
        $this->assertCount(2, $page2);
        $this->assertNotSame($page1[0]->id, $page2[0]->id);
    }

    public function testCountMatchesTotalRowsAndRespectsStatusFilter(): void
    {
        $this->repo->create('Перша', 'first@example.com', null, null, null, 'Перше повідомлення.', true);
        $closedId = $this->repo->create('Друга', 'second@example.com', null, null, null, 'Друге повідомлення.', true);
        $this->repo->updateStatus($closedId, RequestStatus::CLOSED);

        $this->assertSame(2, $this->repo->count());
        $this->assertSame(1, $this->repo->count(RequestStatus::CLOSED));
        $this->assertSame(1, $this->repo->count(RequestStatus::NEW));
    }
}
