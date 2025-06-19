<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * All SQL for the `requests` table in one place, instead of inline PDO
 * calls scattered across admin_requests.php / requests.php /
 * create_request.php / edit_request.php / delete_request.php / api.php
 * (the original shape of this project -- see docs/report.md).
 */
final class RequestRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return RequestRecord[] */
    public function all(?string $status = null, int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT * FROM requests';
        $params = [];

        if ($status !== null) {
            $sql .= ' WHERE status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY id DESC LIMIT ? OFFSET ?';

        $stmt = $this->pdo->prepare($sql);
        $position = 1;
        foreach ($params as $param) {
            $stmt->bindValue($position++, $param);
        }
        // MySQL's PDO driver rejects quoted ('10') LIMIT/OFFSET values --
        // PDOStatement::execute() with a plain array binds everything as a
        // string, which SQLite tolerates but MySQL doesn't. These two need
        // an explicit PDO::PARAM_INT bind (caught by a live MySQL run, not
        // the SQLite-backed unit tests -- see docs/report.md).
        $stmt->bindValue($position++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($position++, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            static fn (array $row): RequestRecord => RequestRecord::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function count(?string $status = null): int
    {
        $sql = 'SELECT COUNT(*) FROM requests';
        $params = [];

        if ($status !== null) {
            $sql .= ' WHERE status = ?';
            $params[] = $status;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?RequestRecord
    {
        $stmt = $this->pdo->prepare('SELECT * FROM requests WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? RequestRecord::fromRow($row) : null;
    }

    public function create(
        string $name,
        string $email,
        ?string $phone,
        ?string $age,
        ?string $color,
        string $message,
        bool $consent
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO requests (name, email, phone, age, color, message, consent, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, $phone, $age, $color, $message, $consent ? 1 : 0, RequestStatus::NEW]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $name, string $email, ?string $phone, string $message): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE requests SET name = ?, email = ?, phone = ?, message = ? WHERE id = ?'
        );
        $stmt->execute([$name, $email, $phone, $message, $id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE requests SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM requests WHERE id = ?');
        $stmt->execute([$id]);
    }
}
