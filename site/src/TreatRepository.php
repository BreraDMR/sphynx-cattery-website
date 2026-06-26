<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * All SQL for the `treats` table -- a near-copy of CatRepository (same
 * draft/published split, same transliterated unique slugs), kept as a
 * separate class rather than abstracted so each catalog stays easy to read
 * and evolve independently for this learning project.
 */
final class TreatRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return TreatRecord[] */
    public function publishedAll(?string $category = null): array
    {
        $sql = "SELECT * FROM treats WHERE status = 'published'";
        $params = [];

        if ($category !== null) {
            $sql .= ' AND category = ?';
            $params[] = $category;
        }

        $sql .= ' ORDER BY id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map(
            static fn (array $row): TreatRecord => TreatRecord::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /** @return TreatRecord[] */
    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM treats ORDER BY id DESC');

        return array_map(
            static fn (array $row): TreatRecord => TreatRecord::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function find(int $id): ?TreatRecord
    {
        $stmt = $this->pdo->prepare('SELECT * FROM treats WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? TreatRecord::fromRow($row) : null;
    }

    public function findBySlug(string $slug): ?TreatRecord
    {
        $stmt = $this->pdo->prepare('SELECT * FROM treats WHERE slug = ?');
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? TreatRecord::fromRow($row) : null;
    }

    public function findPublishedBySlug(string $slug): ?TreatRecord
    {
        $stmt = $this->pdo->prepare("SELECT * FROM treats WHERE slug = ? AND status = 'published'");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? TreatRecord::fromRow($row) : null;
    }

    public function create(
        string $name,
        string $category,
        int $priceEur,
        int $weightG,
        string $description,
        ?string $photoPath,
        string $status,
        ?string $createdBy
    ): TreatRecord {
        $slug = $this->uniqueSlug($name);

        $stmt = $this->pdo->prepare(
            'INSERT INTO treats (slug, name, category, price_eur, weight_g, description, photo_path, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$slug, $name, $category, $priceEur, $weightG, $description, $photoPath, $status, $createdBy]);

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function updatePhoto(int $id, string $photoPath): void
    {
        $stmt = $this->pdo->prepare('UPDATE treats SET photo_path = ? WHERE id = ?');
        $stmt->execute([$photoPath, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM treats WHERE id = ?');
        $stmt->execute([$id]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = CatRepository::slugify($name);
        $slug = $base;
        $suffix = 2;

        while ($this->findBySlug($slug) !== null) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
