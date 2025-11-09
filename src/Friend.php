<?php

final class Friend
{
    /** Are users already friends? */
    public static function areFriends(int $u1, int $u2): bool {
        if ($u1 === $u2) return false;
        $a = min($u1, $u2);
        $b = max($u1, $u2);
        $st = db()->prepare("SELECT 1 FROM friendships WHERE user_id_a = :a AND user_id_b = :b LIMIT 1");
        $st->execute([':a'=>$a, ':b'=>$b]);
        return (bool)$st->fetchColumn();
    }

    /** Current pending request between users (any direction), or null. */
    public static function pendingBetween(int $u1, int $u2): ?array {
        $st = db()->prepare("
          SELECT * FROM friend_requests
          WHERE ((requester_id = :u1 AND addressee_id = :u2) OR (requester_id = :u2 AND addressee_id = :u1))
            AND status = 'pending'
          ORDER BY created_at DESC
          LIMIT 1
        ");
        $st->execute([':u1'=>$u1, ':u2'=>$u2]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** Send a friend request (uFrom -> uTo). */
    public static function sendRequest(int $uFrom, int $uTo): void {
        if ($uFrom === $uTo) throw new RuntimeException('Magadat nem jelölheted be.');
        if (self::areFriends($uFrom, $uTo)) throw new RuntimeException('Már barátok vagytok.');
        if (self::pendingBetween($uFrom, $uTo)) throw new RuntimeException('Már van függőben lévő felkérés.');

        $st = db()->prepare("INSERT INTO friend_requests (requester_id, addressee_id, status, created_at) VALUES (:r,:a,'pending',NOW())");
        $st->execute([':r'=>$uFrom, ':a'=>$uTo]);
    }

    /** Cancel a request you sent. */
    public static function cancelSent(int $uFrom, int $uTo): void {
        $st = db()->prepare("UPDATE friend_requests SET status='canceled', decided_at=NOW()
                             WHERE requester_id=:r AND addressee_id=:a AND status='pending'");
        $st->execute([':r'=>$uFrom, ':a'=>$uTo]);
    }

    /** Decline a request you received. */
    public static function declineReceived(int $uTo, int $uFrom): void {
        $st = db()->prepare("UPDATE friend_requests SET status='declined', decided_at=NOW()
                             WHERE requester_id=:r AND addressee_id=:a AND status='pending'");
        $st->execute([':r'=>$uFrom, ':a'=>$uTo]);
    }

    /** Accept a request you received → creates friendship + marks accepted. */
    public static function acceptReceived(int $uTo, int $uFrom): void {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Lock the request row
            $st = $pdo->prepare("SELECT * FROM friend_requests WHERE requester_id=:r AND addressee_id=:a AND status='pending' FOR UPDATE");
            $st->execute([':r'=>$uFrom, ':a'=>$uTo]);
            $req = $st->fetch();
            if (!$req) throw new RuntimeException('Nincs függőben lévő felkérés.');

            // Create friendship if not exists
            $a = min($uFrom, $uTo);
            $b = max($uFrom, $uTo);
            $pdo->prepare("INSERT IGNORE INTO friendships (user_id_a, user_id_b, since) VALUES (:a,:b,NOW())")
                ->execute([':a'=>$a, ':b'=>$b]);

            // Mark accepted
            $pdo->prepare("UPDATE friend_requests SET status='accepted', decided_at=NOW()
                           WHERE requester_id=:r AND addressee_id=:a AND status='pending'")
                ->execute([':r'=>$uFrom, ':a'=>$uTo]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Remove an existing friendship. */
    public static function unfriend(int $u1, int $u2): void {
        $a = min($u1, $u2);
        $b = max($u1, $u2);
        db()->prepare("DELETE FROM friendships WHERE user_id_a=:a AND user_id_b=:b")->execute([':a'=>$a, ':b'=>$b]);
    }

    /** Lists */
    public static function friendsOf(int $userId): array {
        $sql = "
          SELECT u.user_id, u.username, f.since
          FROM friendships f
          JOIN users u ON u.user_id = CASE WHEN f.user_id_a = :u THEN f.user_id_b ELSE f.user_id_a END
          WHERE f.user_id_a = :u OR f.user_id_b = :u
          ORDER BY u.username
        ";
        $st = db()->prepare($sql);
        $st->execute([':u'=>$userId]);
        return $st->fetchAll();
    }

    public static function pendingReceived(int $userId): array {
        $st = db()->prepare("
          SELECT fr.*, u.username AS from_username
          FROM friend_requests fr
          JOIN users u ON u.user_id = fr.requester_id
          WHERE fr.addressee_id = :u AND fr.status='pending'
          ORDER BY fr.created_at DESC
        ");
        $st->execute([':u'=>$userId]);
        return $st->fetchAll();
    }

    public static function pendingSent(int $userId): array {
        $st = db()->prepare("
          SELECT fr.*, u.username AS to_username
          FROM friend_requests fr
          JOIN users u ON u.user_id = fr.addressee_id
          WHERE fr.requester_id = :u AND fr.status='pending'
          ORDER BY fr.created_at DESC
        ");
        $st->execute([':u'=>$userId]);
        return $st->fetchAll();
    }
}
