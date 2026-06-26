<?php

declare(strict_types=1);

namespace App;

/**
 * Immutable read model for one row of the `treats` table -- the "вкусняшки"
 * (treats / care products) catalog, a sibling of CatRecord. Like cats, the
 * cards are added by the admin through the Telegram bot (api/treats.php).
 *
 * `category` is a locale-independent canonical key ('snacks', 'food', ...);
 * the human label is translated in the UI (see lang/*.php 'treats.cat.*').
 */
final class TreatRecord
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $name,
        public readonly string $category,
        public readonly int $priceEur,
        public readonly int $weightG,
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
            category: (string) $row['category'],
            priceEur: (int) $row['price_eur'],
            weightG: (int) $row['weight_g'],
            description: (string) $row['description'],
            photoPath: $row['photo_path'] !== null ? (string) $row['photo_path'] : null,
            status: (string) $row['status'],
            createdBy: $row['created_by'] !== null ? (string) $row['created_by'] : null,
            createdAt: (string) $row['created_at'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => $this->category,
            'price_eur' => $this->priceEur,
            'weight_g' => $this->weightG,
            'description' => $this->description,
            'photo' => $this->photoPath ?? 'assets/images/treat-placeholder.webp',
            'status' => $this->status,
        ];
    }
}
