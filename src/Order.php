<?php

final class OrderModel
{
    /**
     * Convert the user's active cart into an order + order_items, grant entitlements into libraries.
     * All done inside a single transaction.
     * Returns the new order_id.
     */
    public static function checkout(int $userId): int {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Lock the active cart row (if any) to avoid race conditions
            $st = $pdo->prepare("SELECT cart_id FROM shopping_carts WHERE user_id = :u AND status = 'active' FOR UPDATE");
            $st->execute([':u' => $userId]);
            $cartId = (int)($st->fetchColumn() ?: 0);
            if (!$cartId) {
                throw new RuntimeException('Nincs aktív kosár.');
            }

            // Load items (with price snapshot)
            $st2 = $pdo->prepare("
                SELECT ci.cart_item_id, ci.game_id, ci.quantity, ci.unit_price_at_add
                FROM cart_items ci
                WHERE ci.cart_id = :c
                ORDER BY ci.cart_item_id ASC
            ");
            $st2->execute([':c' => $cartId]);
            $items = $st2->fetchAll();
            if (!$items) {
                throw new RuntimeException('A kosár üres.');
            }

            // Snapshot user billing
            $stU = $pdo->prepare("
                SELECT billing_full_name, billing_address1, billing_address2, billing_city, billing_postal_code, billing_country
                FROM users WHERE user_id = :u LIMIT 1
            ");
            $stU->execute([':u' => $userId]);
            $billing = $stU->fetch() ?: [];

            // Compute total
            $total = 0.0;
            foreach ($items as $it) {
                $total += (float)$it['unit_price_at_add'] * (int)$it['quantity'];
            }

            // Create order
            $stO = $pdo->prepare("
                INSERT INTO orders
                (user_id, status, total_amount, placed_at, payment_method,
                 bill_full_name, bill_address1, bill_address2, bill_city, bill_postal_code, bill_country)
                VALUES
                (:u, 'paid', :tot, NOW(), 'other',
                 :fn, :a1, :a2, :ct, :pc, :cc)
            ");
            $stO->execute([
                ':u'  => $userId,
                ':tot'=> $total,
                ':fn' => $billing['billing_full_name'] ?? null,
                ':a1' => $billing['billing_address1'] ?? null,
                ':a2' => $billing['billing_address2'] ?? null,
                ':ct' => $billing['billing_city'] ?? null,
                ':pc' => $billing['billing_postal_code'] ?? null,
                ':cc' => $billing['billing_country'] ?? null,
            ]);
            $orderId = (int)$pdo->lastInsertId();

            // Order items
            $stOI = $pdo->prepare("
                INSERT INTO order_items (order_id, game_id, quantity, unit_price)
                VALUES (:o, :g, :q, :p)
                ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), unit_price = VALUES(unit_price)
            ");
            foreach ($items as $it) {
                $stOI->execute([
                    ':o' => $orderId,
                    ':g' => (int)$it['game_id'],
                    ':q' => (int)$it['quantity'],
                    ':p' => (float)$it['unit_price_at_add'],
                ]);
            }

            // Grant entitlements
            $stLib = $pdo->prepare("
                INSERT IGNORE INTO libraries (user_id, game_id, acquired_at, source)
                VALUES (:u, :g, NOW(), 'purchase')
            ");
            foreach ($items as $it) {
                $stLib->execute([':u' => $userId, ':g' => (int)$it['game_id']]);
            }

            // Mark cart converted & clear items
            $pdo->prepare("UPDATE shopping_carts SET status = 'converted', updated_at = NOW() WHERE cart_id = :c")->execute([':c' => $cartId]);
            $pdo->prepare("DELETE FROM cart_items WHERE cart_id = :c")->execute([':c' => $cartId]);

            $pdo->commit();
            return $orderId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function listForUser(
        int $userId,
        ?string $status = null,
        ?string $from = null,   // 'YYYY-MM-DD'
        ?string $to = null,     // 'YYYY-MM-DD'
        int $page = 1,
        int $perPage = 10
    ): array {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = ["o.user_id = :u"];
        $p = [':u' => $userId];

        if ($status && $status !== '') {
            $where[] = "o.status = :s";
            $p[':s'] = $status;
        }
        if ($from && $from !== '') {
            $where[] = "DATE(o.placed_at) >= :from";
            $p[':from'] = $from;
        }
        if ($to && $to !== '') {
            $where[] = "DATE(o.placed_at) <= :to";
            $p[':to'] = $to;
        }

        $w = 'WHERE ' . implode(' AND ', $where);

        $stC = db()->prepare("SELECT COUNT(*) FROM orders o $w");
        $stC->execute($p);
        $total = (int)$stC->fetchColumn();

        $st = db()->prepare("
          SELECT o.order_id, o.status, o.total_amount, o.placed_at, COUNT(oi.game_id) AS items
          FROM orders o
          LEFT JOIN order_items oi ON oi.order_id = o.order_id
          $w
          GROUP BY o.order_id, o.status, o.total_amount, o.placed_at
          ORDER BY o.placed_at DESC, o.order_id DESC
          LIMIT :limit OFFSET :offset
        ");
        foreach ($p as $k => $v) $st->bindValue($k, $v);
        $st->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();

        return ['rows' => $st->fetchAll(), 'total' => $total];
    }

    public static function getOne(int $userId, int $orderId): ?array {
        // Header + billing
        $st = db()->prepare("
          SELECT o.order_id, o.user_id, o.status, o.total_amount, o.placed_at, o.payment_method,
                 o.bill_full_name, o.bill_address1, o.bill_address2, o.bill_city, o.bill_postal_code, o.bill_country
          FROM orders o
          WHERE o.order_id = :o AND o.user_id = :u
          LIMIT 1
        ");
        $st->execute([':o' => $orderId, ':u' => $userId]);
        $order = $st->fetch();
        if (!$order) return null;

        // Items
        $st2 = db()->prepare("
          SELECT oi.game_id, oi.quantity, oi.unit_price,
                 g.title, g.image_url
          FROM order_items oi
          LEFT JOIN games g ON g.game_id = oi.game_id
          WHERE oi.order_id = :o
          ORDER BY g.title
        ");
        $st2->execute([':o' => $orderId]);
        $order['items'] = $st2->fetchAll();

        return $order;
    }
}
