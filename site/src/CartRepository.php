<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * Shopping cart storage (`cart_items`). A cart belongs to a logged-in user;
 * each row is one product (a cat or a treat) with a quantity. Cats are
 * one-of-a-kind, so their quantity is always clamped to 1; treats can be
 * bought in multiples.
 *
 * The repository deals only in raw cart rows + quantities; resolving each
 * row to its product card (name/price/photo) is the caller's job, via
 * CatRepository / TreatRepository (see cart.php).
 */
final class CartRepository
{
    public const TYPE_CAT = 'cat';
    public const TYPE_TREAT = 'treat';

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Total number of items (sum of quantities) -- drives the header badge.
     */
    public function count(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(qty), 0) FROM cart_items WHERE user_id = ?');
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    /** @return array<int, array{id:int,item_type:string,item_id:int,qty:int}> */
    public function items(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, item_type, item_id, qty FROM cart_items WHERE user_id = ? ORDER BY id');
        $stmt->execute([$userId]);

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'item_type' => (string) $row['item_type'],
            'item_id' => (int) $row['item_id'],
            'qty' => (int) $row['qty'],
        ], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Add a product to the cart (or bump its quantity if already there).
     * Cats can never exceed qty 1.
     */
    public function add(int $userId, string $type, int $itemId, int $qty = 1): void
    {
        $qty = max(1, $qty);

        $stmt = $this->pdo->prepare('SELECT id, qty FROM cart_items WHERE user_id = ? AND item_type = ? AND item_id = ?');
        $stmt->execute([$userId, $type, $itemId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing !== false) {
            $newQty = $type === self::TYPE_CAT ? 1 : ((int) $existing['qty'] + $qty);
            $this->updateQty($userId, (int) $existing['id'], $newQty);
            return;
        }

        if ($type === self::TYPE_CAT) {
            $qty = 1;
        }

        $stmt = $this->pdo->prepare('INSERT INTO cart_items (user_id, item_type, item_id, qty) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $type, $itemId, $qty]);
    }

    public function updateQty(int $userId, int $cartItemId, int $qty): void
    {
        if ($qty <= 0) {
            $this->remove($userId, $cartItemId);
            return;
        }

        $stmt = $this->pdo->prepare('UPDATE cart_items SET qty = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$qty, $cartItemId, $userId]);
    }

    public function remove(int $userId, int $cartItemId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?');
        $stmt->execute([$cartItemId, $userId]);
    }

    public function clear(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM cart_items WHERE user_id = ?');
        $stmt->execute([$userId]);
    }
}
