<?php

declare(strict_types=1);

namespace Tests;

use App\CartRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class CartRepositoryTest extends TestCase
{
    private PDO $pdo;
    private CartRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            'CREATE TABLE cart_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                item_type TEXT NOT NULL,
                item_id INTEGER NOT NULL,
                qty INTEGER NOT NULL DEFAULT 1,
                added_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (user_id, item_type, item_id)
            )'
        );

        $this->repo = new CartRepository($this->pdo);
    }

    public function testAddingACatTwiceKeepsQuantityAtOne(): void
    {
        $this->repo->add(1, CartRepository::TYPE_CAT, 10);
        $this->repo->add(1, CartRepository::TYPE_CAT, 10);

        $items = $this->repo->items(1);
        $this->assertCount(1, $items);
        $this->assertSame(1, $items[0]['qty']);
    }

    public function testAddingATreatAccumulatesQuantity(): void
    {
        $this->repo->add(1, CartRepository::TYPE_TREAT, 5, 1);
        $this->repo->add(1, CartRepository::TYPE_TREAT, 5, 2);

        $items = $this->repo->items(1);
        $this->assertCount(1, $items);
        $this->assertSame(3, $items[0]['qty']);
    }

    public function testCountSumsQuantitiesForTheUser(): void
    {
        $this->repo->add(1, CartRepository::TYPE_TREAT, 5, 2);
        $this->repo->add(1, CartRepository::TYPE_CAT, 10);
        $this->repo->add(2, CartRepository::TYPE_TREAT, 7, 4); // другой пользователь

        $this->assertSame(3, $this->repo->count(1));
        $this->assertSame(4, $this->repo->count(2));
    }

    public function testUpdateQtyToZeroRemovesTheRow(): void
    {
        $this->repo->add(1, CartRepository::TYPE_TREAT, 5, 3);
        $cartItemId = $this->repo->items(1)[0]['id'];

        $this->repo->updateQty(1, $cartItemId, 0);

        $this->assertSame([], $this->repo->items(1));
    }

    public function testRemoveAndClear(): void
    {
        $this->repo->add(1, CartRepository::TYPE_TREAT, 5, 1);
        $this->repo->add(1, CartRepository::TYPE_TREAT, 6, 1);
        $firstId = $this->repo->items(1)[0]['id'];

        $this->repo->remove(1, $firstId);
        $this->assertCount(1, $this->repo->items(1));

        $this->repo->clear(1);
        $this->assertSame(0, $this->repo->count(1));
    }
}
