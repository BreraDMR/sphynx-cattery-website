<?php

declare(strict_types=1);

namespace App;

/**
 * Immutable read model for one row of the `users` table.
 *
 * passwordHash may be null for accounts created purely through an OAuth
 * provider (Google/GitHub) -- those users have no local password and can
 * only sign in through that provider.
 */
final class UserRecord
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly ?string $passwordHash,
        public readonly string $name,
        public readonly ?string $oauthProvider,
        public readonly ?string $oauthId,
        public readonly string $role,
        public readonly string $createdAt,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            email: (string) $row['email'],
            passwordHash: $row['password_hash'] !== null ? (string) $row['password_hash'] : null,
            name: (string) $row['name'],
            oauthProvider: $row['oauth_provider'] !== null ? (string) $row['oauth_provider'] : null,
            oauthId: $row['oauth_id'] !== null ? (string) $row['oauth_id'] : null,
            role: (string) $row['role'],
            createdAt: (string) $row['created_at'],
        );
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
