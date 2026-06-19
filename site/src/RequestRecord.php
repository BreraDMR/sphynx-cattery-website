<?php

declare(strict_types=1);

namespace App;

/**
 * Immutable read model for one row of the `requests` table.
 */
final class RequestRecord
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly ?string $age,
        public readonly ?string $color,
        public readonly string $message,
        public readonly bool $consent,
        public readonly string $status,
        public readonly string $createdAt,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            email: (string) $row['email'],
            phone: $row['phone'] !== null ? (string) $row['phone'] : null,
            age: $row['age'] !== null ? (string) $row['age'] : null,
            color: $row['color'] !== null ? (string) $row['color'] : null,
            message: (string) $row['message'],
            consent: (bool) $row['consent'],
            status: (string) ($row['status'] ?? RequestStatus::NEW),
            createdAt: (string) $row['created_at'],
        );
    }
}
