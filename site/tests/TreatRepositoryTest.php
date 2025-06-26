<?php

declare(strict_types=1);

namespace Tests;

use App\TreatRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class TreatRepositoryTest extends TestCase
{
    private PDO $pdo;
    private TreatRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            'CREATE TABLE treats (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                category TEXT NOT NULL,
                price_eur INTEGER NOT NULL,
                weight_g INTEGER NOT NULL DEFAULT 0,
                description TEXT NOT NULL,
                photo_path TEXT NULL,
                status TEXT NOT NULL DEFAULT "published",
                created_by TEXT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $this->repo = new TreatRepository($this->pdo);
    }

    public function testCreateGeneratesASlugAndDeduplicates(): void
    {
        $first = $this->repo->create('Chicken Jerky Bites', 'snacks', 6, 80, 'Tasty chicken bites.', null, 'published', 'seed');
        $second = $this->repo->create('Chicken Jerky Bites', 'snacks', 6, 80, 'Another batch, same name.', null, 'published', 'seed');

        $this->assertSame('chicken-jerky-bites', $first->slug);
        $this->assertSame('chicken-jerky-bites-2', $second->slug);
    }

    public function testPublishedAllExcludesDraftsAndFiltersByCategory(): void
    {
        $this->repo->create('Snack A', 'snacks', 6, 80, 'A published snack.', null, 'published', null);
        $this->repo->create('Food B', 'food', 19, 2000, 'A published food.', null, 'published', null);
        $this->repo->create('Draft C', 'snacks', 6, 80, 'A draft snack.', null, 'draft', null);

        $this->assertCount(2, $this->repo->publishedAll());
        $snacks = $this->repo->publishedAll('snacks');
        $this->assertCount(1, $snacks);
        $this->assertSame('Snack A', $snacks[0]->name);
    }

    public function testFindPublishedBySlugRespectsStatus(): void
    {
        $treat = $this->repo->create('Hidden', 'toys', 8, 0, 'A drafted toy.', null, 'draft', null);

        $this->assertNotNull($this->repo->findBySlug($treat->slug));
        $this->assertNull($this->repo->findPublishedBySlug($treat->slug));
    }

    public function testDeleteRemovesTheRow(): void
    {
        $treat = $this->repo->create('Gone', 'care', 9, 120, 'To be removed.', null, 'published', null);

        $this->repo->delete($treat->id);

        $this->assertNull($this->repo->find($treat->id));
    }
}
