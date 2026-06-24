<?php

declare(strict_types=1);

namespace App;

/**
 * Immutable read model for one row of the `cats` table.
 */
final class CatRecord
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $name,
        public readonly string $color,
        public readonly int $ageMonths,
        public readonly int $priceEur,
        public readonly string $description,
        public readonly ?string $photoPath,
        public readonly string $status,
        public readonly ?string $createdBy,
        public readonly string $createdAt,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            slug: (string) $row['slug'],
            name: (string) $row['name'],
            color: (string) $row['color'],
            ageMonths: (int) $row['age_months'],
            priceEur: (int) $row['price_eur'],
            description: (string) $row['description'],
            photoPath: $row['photo_path'] !== null ? (string) $row['photo_path'] : null,
            status: (string) $row['status'],
            createdBy: $row['created_by'] !== null ? (string) $row['created_by'] : null,
            createdAt: (string) $row['created_at'],
        );
    }

    /**
     * Ukrainian "X місяців" label with correct plural form -- 1 місяць,
     * 2-4 місяці, 0/5+ місяців (standard Slavic plural rule).
     */
    public function ageLabel(): string
    {
        $n = $this->ageMonths;
        $mod10 = $n % 10;
        $mod100 = $n % 100;

        $word = match (true) {
            $mod10 === 1 && $mod100 !== 11 => 'місяць',
            in_array($mod10, [2, 3, 4], true) && !in_array($mod100, [12, 13, 14], true) => 'місяці',
            default => 'місяців',
        };

        return "{$n} {$word}";
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'color' => $this->color,
            'age_months' => $this->ageMonths,
            'age_label' => $this->ageLabel(),
            'price_eur' => $this->priceEur,
            'description' => $this->description,
            'photo' => $this->photoPath ?? 'assets/images/sphynx-black.webp',
            'status' => $this->status,
        ];
    }
}
