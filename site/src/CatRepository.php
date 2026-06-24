<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * All SQL for the `cats` table in one place -- mirrors RequestRepository's
 * shape. Rows with status='draft' are kept out of publishedAll() so a card
 * added via the Telegram bot can be reviewed before it appears on the public
 * catalog (api/cats.php only calls publishedAll()/findPublishedBySlug()).
 */
final class CatRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return CatRecord[] */
    public function publishedAll(?string $color = null): array
    {
        $sql = "SELECT * FROM cats WHERE status = 'published'";
        $params = [];

        if ($color !== null) {
            $sql .= ' AND color = ?';
            $params[] = $color;
        }

        $sql .= ' ORDER BY id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map(
            static fn (array $row): CatRecord => CatRecord::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /** @return CatRecord[] */
    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM cats ORDER BY id DESC');

        return array_map(
            static fn (array $row): CatRecord => CatRecord::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function find(int $id): ?CatRecord
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cats WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? CatRecord::fromRow($row) : null;
    }

    public function findBySlug(string $slug): ?CatRecord
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cats WHERE slug = ?');
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? CatRecord::fromRow($row) : null;
    }

    public function findPublishedBySlug(string $slug): ?CatRecord
    {
        $stmt = $this->pdo->prepare("SELECT * FROM cats WHERE slug = ? AND status = 'published'");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? CatRecord::fromRow($row) : null;
    }

    public function create(
        string $name,
        string $color,
        int $ageMonths,
        int $priceEur,
        string $description,
        ?string $photoPath,
        string $status,
        ?string $createdBy
    ): CatRecord {
        $slug = $this->uniqueSlug($name);

        $stmt = $this->pdo->prepare(
            'INSERT INTO cats (slug, name, color, age_months, price_eur, description, photo_path, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$slug, $name, $color, $ageMonths, $priceEur, $description, $photoPath, $status, $createdBy]);

        $id = (int) $this->pdo->lastInsertId();

        return $this->find($id);
    }

    public function updatePhoto(int $id, string $photoPath): void
    {
        $stmt = $this->pdo->prepare('UPDATE cats SET photo_path = ? WHERE id = ?');
        $stmt->execute([$photoPath, $id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE cats SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM cats WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Transliterates a Cyrillic (or Latin) name into a URL-safe slug and
     * appends -2, -3, ... if it already exists -- new cards come from a
     * Telegram bot, so silently colliding on "макс"/"макс" twice has to be
     * handled here rather than assumed away.
     */
    private function uniqueSlug(string $name): string
    {
        $base = self::slugify($name);
        $slug = $base;
        $suffix = 2;

        while ($this->findBySlug($slug) !== null) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public static function slugify(string $name): string
    {
        static $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'h', 'ґ' => 'g', 'д' => 'd',
            'е' => 'e', 'є' => 'ie', 'ж' => 'zh', 'з' => 'z', 'и' => 'y', 'і' => 'i',
            'ї' => 'i', 'й' => 'i', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
            'ь' => '', 'ю' => 'iu', 'я' => 'ia', "'" => '', '’' => '',
        ];

        $transliterated = strtr(mb_strtolower($name), $map);
        $slug = preg_replace('/[^a-z0-9]+/u', '-', $transliterated);
        $slug = trim((string) $slug, '-');

        return $slug !== '' ? $slug : 'cat';
    }
}
