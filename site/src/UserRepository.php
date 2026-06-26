<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * All SQL for the `users` table in one place -- mirrors CatRepository's
 * shape. Accounts are created either with a bcrypt password (email/password
 * sign-up) or linked to an OAuth provider (Google/GitHub), in which case
 * password_hash stays null and (oauth_provider, oauth_id) identifies them.
 */
final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function find(int $id): ?UserRecord
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? UserRecord::fromRow($row) : null;
    }

    public function findByEmail(string $email): ?UserRecord
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([mb_strtolower(trim($email))]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? UserRecord::fromRow($row) : null;
    }

    public function findByOauth(string $provider, string $oauthId): ?UserRecord
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE oauth_provider = ? AND oauth_id = ?');
        $stmt->execute([$provider, $oauthId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? UserRecord::fromRow($row) : null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE email = ?');
        $stmt->execute([mb_strtolower(trim($email))]);

        return $stmt->fetchColumn() !== false;
    }

    public function create(
        string $email,
        ?string $passwordHash,
        string $name,
        string $role = 'user',
        ?string $oauthProvider = null,
        ?string $oauthId = null
    ): UserRecord {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, password_hash, name, role, oauth_provider, oauth_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([mb_strtolower(trim($email)), $passwordHash, $name, $role, $oauthProvider, $oauthId]);

        return $this->find((int) $this->pdo->lastInsertId());
    }

    /**
     * Attach an OAuth provider to an existing (email-matched) account, so a
     * user who first registered with a password can later click "Sign in with
     * Google" using the same email and have it linked rather than rejected.
     */
    public function linkOauth(int $id, string $provider, string $oauthId): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET oauth_provider = ?, oauth_id = ? WHERE id = ?');
        $stmt->execute([$provider, $oauthId, $id]);
    }
}
