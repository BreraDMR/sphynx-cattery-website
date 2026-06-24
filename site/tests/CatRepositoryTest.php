<?php

declare(strict_types=1);

namespace Tests;

use App\CatRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class CatRepositoryTest extends TestCase
{
    private PDO $pdo;
    private CatRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            'CREATE TABLE cats (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                color TEXT NOT NULL,
                age_months INTEGER NOT NULL,
                price_eur INTEGER NOT NULL,
                description TEXT NOT NULL,
                photo_path TEXT NULL,
                status TEXT NOT NULL DEFAULT "published",
                created_by TEXT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $this->repo = new CatRepository($this->pdo);
    }

    public function testCreateGeneratesATransliteratedSlug(): void
    {
        $cat = $this->repo->create('Чорний Сфінкс Макс', 'чорний', 3, 1450, 'Дуже грайливе кошеня.', null, 'published', 'bot:owner');

        $this->assertSame('chornyi-sfinks-maks', $cat->slug);
        $this->assertSame('published', $cat->status);
    }

    public function testCreateDeduplicatesCollidingSlugs(): void
    {
        $first = $this->repo->create('Макс', 'чорний', 3, 1450, 'Перше кошеня з цим іменем.', null, 'published', null);
        $second = $this->repo->create('Макс', 'чорний', 4, 1500, 'Друге кошеня з тим самим іменем.', null, 'published', null);

        $this->assertSame('maks', $first->slug);
        $this->assertSame('maks-2', $second->slug);
    }

    public function testPublishedAllExcludesDrafts(): void
    {
        $this->repo->create('Опублікований', 'білий', 2, 1500, 'Видимий у каталозі.', null, 'published', null);
        $this->repo->create('Чернетка', 'білий', 2, 1500, 'Ще не підтверджений кошеня.', null, 'draft', null);

        $published = $this->repo->publishedAll();

        $this->assertCount(1, $published);
        $this->assertSame('Опублікований', $published[0]->name);
    }

    public function testPublishedAllFiltersByColor(): void
    {
        $this->repo->create('Чорний', 'чорний', 2, 1500, 'Чорне кошеня для тесту.', null, 'published', null);
        $this->repo->create('Білий', 'білий', 2, 1500, 'Біле кошеня для тесту.', null, 'published', null);

        $blackOnly = $this->repo->publishedAll('чорний');

        $this->assertCount(1, $blackOnly);
        $this->assertSame('чорний', $blackOnly[0]->color);
    }

    public function testFindBySlugAndPublishedBySlug(): void
    {
        $cat = $this->repo->create('Тест', 'інший', 2, 1000, 'Кошеня для перевірки пошуку.', null, 'draft', null);

        $this->assertNotNull($this->repo->findBySlug($cat->slug));
        $this->assertNull($this->repo->findPublishedBySlug($cat->slug));

        $this->repo->updateStatus($cat->id, 'published');
        $this->assertNotNull($this->repo->findPublishedBySlug($cat->slug));
    }

    public function testUpdatePhotoChangesOnlyThePhotoPath(): void
    {
        $cat = $this->repo->create('Фото', 'інший', 2, 1000, 'Кошеня без фото поки що.', null, 'published', null);

        $this->repo->updatePhoto($cat->id, 'assets/images/cats/fото-abc123.webp');
        $updated = $this->repo->find($cat->id);

        $this->assertSame('assets/images/cats/fото-abc123.webp', $updated->photoPath);
        $this->assertSame('Фото', $updated->name);
    }

    public function testDeleteRemovesTheRow(): void
    {
        $cat = $this->repo->create('Видалити', 'інший', 2, 1000, 'Кошеня, яке буде видалено.', null, 'published', null);

        $this->repo->delete($cat->id);

        $this->assertNull($this->repo->find($cat->id));
    }
}
