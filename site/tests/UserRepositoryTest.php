<?php

declare(strict_types=1);

namespace Tests;

use App\UserRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class UserRepositoryTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NULL,
                name TEXT NOT NULL,
                oauth_provider TEXT NULL,
                oauth_id TEXT NULL,
                role TEXT NOT NULL DEFAULT "user",
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $this->repo = new UserRepository($this->pdo);
    }

    public function testCreateAndFindByEmailIsCaseInsensitive(): void
    {
        $user = $this->repo->create('Test@Example.com', password_hash('secret123', PASSWORD_BCRYPT), 'Tester');

        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('user', $user->role);

        $found = $this->repo->findByEmail('TEST@EXAMPLE.COM');
        $this->assertNotNull($found);
        $this->assertSame($user->id, $found->id);
    }

    public function testEmailExists(): void
    {
        $this->repo->create('a@b.com', null, 'A', 'user', 'google', '123');

        $this->assertTrue($this->repo->emailExists('A@B.com'));
        $this->assertFalse($this->repo->emailExists('nobody@b.com'));
    }

    public function testFindByOauthAndLinkOauth(): void
    {
        $user = $this->repo->create('link@b.com', password_hash('pw', PASSWORD_BCRYPT), 'Linker');
        $this->assertNull($this->repo->findByOauth('github', '999'));

        $this->repo->linkOauth($user->id, 'github', '999');
        $linked = $this->repo->findByOauth('github', '999');

        $this->assertNotNull($linked);
        $this->assertSame($user->id, $linked->id);
        $this->assertSame('github', $linked->oauthProvider);
    }

    public function testOauthOnlyUserHasNullPasswordHash(): void
    {
        $user = $this->repo->create('oauth@b.com', null, 'OAuth User', 'user', 'google', 'sub-1');

        $this->assertNull($user->passwordHash);
        $this->assertFalse($user->isAdmin());
    }
}
