<?php

final class Cart
{
    /** Get active cart id for user, creating one if missing. */
    public static function ensureActiveCartId(int $userId): int {
        // Try to find an active cart
        $st = db()->prepare("SELECT cart_id FROM shopping_carts WHERE user_id = :u AND status = 'active' LIMIT 1");
        $st->execute([':u' => $userId]);
        $id = $st->fetchColumn();
        if ($id) return (int)$id;

        // Create one
        $st = db()->prepare("INSERT INTO shopping_carts (user_id, status, created_at, updated_at) VALUES (:u, 'active', NOW(), NOW())");
        $st->execute([':u' => $userId]);
        return (int)db()->lastInsertId();
    }

    /** Add or increase a game in the cart. */
    public static function addItem(int $userId, int $gameId, int $quantity = 1): void {
        $quantity = max(1, $quantity);
        $cartId = self::ensureActiveCartId($userId);

        // Snapshot the current price of the game
        $stG = db()->prepare("SELECT price FROM games WHERE game_id = :g LIMIT 1");
        $stG->execute([':g' => $gameId]);
        $price = $stG->fetchColumn();
        if ($price === false) {
            throw new RuntimeException('A játék nem található.');
        }

        // Upsert: one line per game per cart
        // First try update quantity if exists
        $stU = db()->prepare("UPDATE cart_items
                              SET quantity = quantity + :q, added_at = NOW()
                              WHERE cart_id = :c AND game_id = :g");
        $stU->execute([':q' => $quantity, ':c' => $cartId, ':g' => $gameId]);

        if ($stU->rowCount() === 0) {
            $stI = db()->prepare("INSERT INTO cart_items (cart_id, game_id, quantity, unit_price_at_add, added_at)
                                  VALUES (:c, :g, :q, :p, NOW())");
            $stI->execute([':c' => $cartId, ':g' => $gameId, ':q' => $quantity, ':p' => $price]);
        }

        // Touch cart
        db()->prepare("UPDATE shopping_carts SET updated_at = NOW() WHERE cart_id = :c")->execute([':c' => $cartId]);
    }

    /** Current cart item count (sum qty). */
    public static function itemCount(int $userId): int {
        $st = db()->prepare("SELECT SUM(ci.quantity) FROM shopping_carts c
                             LEFT JOIN cart_items ci ON ci.cart_id = c.cart_id
                             WHERE c.user_id = :u AND c.status = 'active'");
        $st->execute([':u' => $userId]);
        return (int)($st->fetchColumn() ?: 0);
    }

    /** Fetch active cart lines with game info. */
    public static function getActiveCart(int $userId): array {
        $st = db()->prepare("
          SELECT c.cart_id, ci.cart_item_id, ci.game_id, g.title, g.image_url,
                 ci.quantity, ci.unit_price_at_add,
                 (ci.quantity * ci.unit_price_at_add) AS line_total
          FROM shopping_carts c
          LEFT JOIN cart_items ci ON ci.cart_id = c.cart_id
          LEFT JOIN games g ON g.game_id = ci.game_id
          WHERE c.user_id = :u AND c.status = 'active'
          ORDER BY ci.added_at DESC
        ");
        $st->execute([':u' => $userId]);
        $rows = $st->fetchAll();

        // Sum totals
        $total = 0.0;
        foreach ($rows as $r) {
            $total += (float)($r['line_total'] ?? 0);
        }
        return ['rows' => $rows, 'total' => $total];
    }

    /** Update a line's quantity (1..99). If qty < 1 => delete line. Ensures the line belongs to the user's active cart. */
    public static function updateItemQuantity(int $userId, int $cartItemId, int $quantity): void {
        $quantity = max(0, min(99, $quantity));
        $cartId = self::ensureActiveCartId($userId);

        if ($quantity === 0) {
            $sql = "DELETE ci FROM cart_items ci
                    WHERE ci.cart_item_id = :ci
                      AND ci.cart_id = :c";
            db()->prepare($sql)->execute([':ci' => $cartItemId, ':c' => $cartId]);
        } else {
            $sql = "UPDATE cart_items ci
                    SET ci.quantity = :q, ci.added_at = NOW()
                    WHERE ci.cart_item_id = :ci AND ci.cart_id = :c";
            db()->prepare($sql)->execute([':q' => $quantity, ':ci' => $cartItemId, ':c' => $cartId]);
        }
        db()->prepare("UPDATE shopping_carts SET updated_at = NOW() WHERE cart_id = :c")->execute([':c' => $cartId]);
    }

    /** Remove a line, ensuring it belongs to the user's active cart. */
    public static function removeItem(int $userId, int $cartItemId): void {
        $cartId = self::ensureActiveCartId($userId);
        $sql = "DELETE ci FROM cart_items ci
                WHERE ci.cart_item_id = :ci
                  AND ci.cart_id = :c";
        db()->prepare($sql)->execute([':ci' => $cartItemId, ':c' => $cartId]);
        db()->prepare("UPDATE shopping_carts SET updated_at = NOW() WHERE cart_id = :c")->execute([':c' => $cartId]);
    }
}
