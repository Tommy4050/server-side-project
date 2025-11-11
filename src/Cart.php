<?php

final class Cart
{
    /** Find or create the active cart for a user, return its id */
    public static function activeCartId(int $userId): int {
        $st = db()->prepare("SELECT cart_id FROM shopping_carts WHERE user_id=:u AND status='active' LIMIT 1");
        $st->execute([':u'=>$userId]);
        $id = (int)$st->fetchColumn();
        if ($id) return $id;

        $st = db()->prepare("INSERT INTO shopping_carts (user_id, status, created_at, updated_at) VALUES (:u,'active',NOW(),NOW())");
        $st->execute([':u'=>$userId]);
        return (int)db()->lastInsertId();
    }

    /** Small summary used by the mini-cart */
    public static function mini(int $userId): array {
        $cartId = self::activeCartId($userId);

        $sql = "
          SELECT ci.cart_item_id, ci.quantity, ci.unit_price_at_add AS unit_price,
                 g.game_id, g.title, g.image_url
          FROM cart_items ci
          JOIN games g ON g.game_id = ci.game_id
          WHERE ci.cart_id = :cid
          ORDER BY g.title
        ";
        $st = db()->prepare($sql);
        $st->execute([':cid'=>$cartId]);
        $rows = $st->fetchAll();

        $total = 0.0; $count = 0;
        foreach ($rows as $r) {
            $total += (float)$r['unit_price'] * (int)$r['quantity'];
            $count += (int)$r['quantity'];
        }
        return ['cart_id'=>$cartId, 'items'=>$rows, 'total'=>$total, 'count'=>$count];
    }

    /** Full cart for the main cart page */
    public static function getActiveCart(int $userId): array {
        $cartId = self::activeCartId($userId);

        // Cart header row (optional, if you store more fields)
        $cartRow = [
            'cart_id'   => $cartId,
            'status'    => 'active',
            'created_at'=> null,
            'updated_at'=> null,
        ];
        try {
            $c = db()->prepare("SELECT cart_id, user_id, status, created_at, updated_at FROM shopping_carts WHERE cart_id=:id LIMIT 1");
            $c->execute([':id'=>$cartId]);
            if ($x = $c->fetch()) {
                $cartRow = $x;
            }
        } catch (Throwable $e) {}

        // Items (you can extend the fields as needed)
        $sql = "
          SELECT ci.cart_item_id, ci.quantity, ci.unit_price_at_add AS unit_price,
                 g.game_id, g.title, g.image_url, g.publisher
          FROM cart_items ci
          JOIN games g ON g.game_id = ci.game_id
          WHERE ci.cart_id = :cid
          ORDER BY g.title
        ";
        $st = db()->prepare($sql);
        $st->execute([':cid'=>$cartId]);
        $items = $st->fetchAll();

        $subtotal = 0.0; $count = 0;
        foreach ($items as &$it) {
            $it['line_total'] = ((float)$it['unit_price']) * (int)$it['quantity'];
            $subtotal += $it['line_total'];
            $count += (int)$it['quantity'];
        }
        unset($it);

        return [
            'cart'     => $cartRow,
            'items'    => $items,
            'subtotal' => $subtotal,
            'count'    => $count,
        ];
    }

    public static function updateQty(int $userId, int $cartItemId, int $qty): void {
        $qty = max(1, min(99, $qty));
        $sql = "UPDATE cart_items ci
                  JOIN shopping_carts c ON c.cart_id = ci.cart_id
                 SET ci.quantity=:q, c.updated_at=NOW()
               WHERE ci.cart_item_id=:id AND c.user_id=:u AND c.status='active'";
        $st = db()->prepare($sql);
        $st->execute([':q'=>$qty, ':id'=>$cartItemId, ':u'=>$userId]);
    }

    public static function removeItem(int $userId, int $cartItemId): void {
        $sql = "DELETE ci FROM cart_items ci
                JOIN shopping_carts c ON c.cart_id = ci.cart_id
               WHERE ci.cart_item_id=:id AND c.user_id=:u AND c.status='active'";
        $st = db()->prepare($sql);
        $st->execute([':id'=>$cartItemId, ':u'=>$userId]);
    }

    public static function addItem(int $userId, int $gameId, int $qty = 1): void {
    $qty = max(1, min(99, $qty));

    // 0) Ensure user doesn’t already own the game (optional, but common)
    $own = db()->prepare("SELECT 1 FROM libraries WHERE user_id=:u AND game_id=:g LIMIT 1");
    $own->execute([':u'=>$userId, ':g'=>$gameId]);
    if ($own->fetchColumn()) {
        throw new RuntimeException('Ez a játék már a könyvtáradban van.');
    }

    // 1) Load game + determine current unit price (respect active sale)
    $gx = db()->prepare("
        SELECT price, is_published, sale_percent, sale_start, sale_end
        FROM games
        WHERE game_id = :g
        LIMIT 1
    ");
    $gx->execute([':g'=>$gameId]);
    $game = $gx->fetch();
    if (!$game) {
        throw new RuntimeException('A játék nem található.');
    }
    if (isset($game['is_published']) && (int)$game['is_published'] !== 1) {
        throw new RuntimeException('A játék nem elérhető.');
    }

    $price = (float)($game['price'] ?? 0);
    $salePercent = (int)($game['sale_percent'] ?? 0);
    $saleStart   = $game['sale_start'] ?? null;
    $saleEnd     = $game['sale_end'] ?? null;

    $today = date('Y-m-d');
    $activeSale = $salePercent > 0
        && (empty($saleStart) || $saleStart <= $today)
        && (empty($saleEnd)   || $saleEnd   >= $today);

    $unit = $price;
    if ($activeSale && $price !== null) {
        $unit = round(((float)$price) * (100 - $salePercent) / 100, 2);
    }

    // 2) Ensure there is an active cart
    $cartId = self::activeCartId($userId);

    // 3) If item already in cart -> bump quantity; else insert new row
    db()->beginTransaction();
    try {
        $st = db()->prepare("SELECT cart_item_id, quantity FROM cart_items WHERE cart_id=:c AND game_id=:g LIMIT 1");
        $st->execute([':c'=>$cartId, ':g'=>$gameId]);
        $row = $st->fetch();

        if ($row) {
            $newQty = max(1, min(99, ((int)$row['quantity']) + $qty));
            $upd = db()->prepare("
                UPDATE cart_items SET quantity=:q WHERE cart_item_id=:id
            ");
            $upd->execute([':q'=>$newQty, ':id'=>(int)$row['cart_item_id']]);
        } else {
            $ins = db()->prepare("
                INSERT INTO cart_items (cart_id, game_id, quantity, unit_price_at_add, created_at)
                VALUES (:c, :g, :q, :p, NOW())
            ");
            // If you don’t have created_at, this NOW() is harmless; remove the column if needed.
            try {
                $ins->execute([':c'=>$cartId, ':g'=>$gameId, ':q'=>$qty, ':p'=>$unit]);
            } catch (Throwable $e) {
                // Fallback for schemas without created_at
                $insNoTs = db()->prepare("
                    INSERT INTO cart_items (cart_id, game_id, quantity, unit_price_at_add)
                    VALUES (:c, :g, :q, :p)
                ");
                $insNoTs->execute([':c'=>$cartId, ':g'=>$gameId, ':q'=>$qty, ':p'=>$unit]);
            }
        }

        // touch cart updated_at if column exists
        try {
            db()->prepare("UPDATE shopping_carts SET updated_at=NOW() WHERE cart_id=:id")->execute([':id'=>$cartId]);
        } catch (Throwable $e) {}

        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
}

}
