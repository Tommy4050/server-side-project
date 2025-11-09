<?php

final class Post
{
    /**
     * Legacy/simple feed (no total). Kept for compatibility.
     * Prefer feedWithTotal() when you need pagination meta.
     */
    public static function feed(
        ?int $userId,
        ?int $gameId = null,
        int $page = 1,
        int $perPage = 10,
        bool $isAdmin = false
    ): array {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $off = ($page - 1) * $perPage;

        $where = [];
        $params = [':uid' => (int)($userId ?? 0)];

        if (!$isAdmin) $where[] = "p.is_hidden = 0";
        if ($gameId)   { $where[] = "p.game_id = :gid"; $params[':gid'] = (int)$gameId; }

        $whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

        $sql = "
          SELECT p.post_id, p.user_id, p.game_id, p.caption, p.image_path, p.is_hidden, p.created_at,
                 u.username,
                 g.title AS game_title,
                 (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.post_id) AS likes_count,
                 (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.post_id AND pc.is_hidden=0) AS comments_count,
                 CASE WHEN EXISTS(
                     SELECT 1 FROM post_likes x
                     WHERE x.post_id = p.post_id AND x.user_id = :uid
                 ) THEN 1 ELSE 0 END AS liked_by_me
          FROM posts p
          JOIN users u ON u.user_id = p.user_id
          LEFT JOIN games g ON g.game_id = p.game_id
          $whereSql
          ORDER BY p.created_at DESC, p.post_id DESC
          LIMIT :lim OFFSET :off
        ";
        $st = db()->prepare($sql);
        foreach ($params as $k => $v) $st->bindValue($k, $v, PDO::PARAM_INT);
        $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $st->bindValue(':off', $off, PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll();
    }

    /**
     * Full feed with pagination metadata.
     * Returns: ['rows' => array, 'total' => int]
     */
    public static function feedWithTotal(
        ?int $viewerUserId,
        ?int $gameId = null,
        ?int $authorUserId = null,
        int $page = 1,
        int $perPage = 10,
        bool $isAdmin = false
    ): array {
        $page    = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset  = ($page - 1) * $perPage;

        $where = [];
        $whereParams = [];

        if (!$isAdmin) {
            $where[] = "p.is_hidden = 0";
        }
        if ($gameId) {
            $where[] = "p.game_id = :gid";
            $whereParams[':gid'] = (int)$gameId;
        }
        if ($authorUserId) {
            $where[] = "p.user_id = :au";
            $whereParams[':au'] = (int)$authorUserId;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // COUNT query (no :uid here)
        $sqlC = "SELECT COUNT(*) FROM posts p $whereSql";
        $stC = db()->prepare($sqlC);
        $stC->execute($whereParams);
        $total = (int)$stC->fetchColumn();

        // ROWS query (add :uid for liked_by_me)
        $rowParams = $whereParams;
        $rowParams[':uid'] = (int)($viewerUserId ?? 0);

        $sql = "
          SELECT p.post_id, p.user_id, p.game_id, p.caption, p.image_path, p.is_hidden, p.created_at,
                 u.username, g.title AS game_title,
                 (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.post_id) AS likes_count,
                 (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.post_id AND pc.is_hidden=0) AS comments_count,
                 CASE WHEN EXISTS(
                     SELECT 1 FROM post_likes x
                     WHERE x.post_id = p.post_id AND x.user_id = :uid
                 ) THEN 1 ELSE 0 END AS liked_by_me
          FROM posts p
          JOIN users u ON u.user_id = p.user_id
          LEFT JOIN games g ON g.game_id = p.game_id
          $whereSql
          ORDER BY p.created_at DESC, p.post_id DESC
          LIMIT :lim OFFSET :off
        ";
        $st = db()->prepare($sql);
        foreach ($rowParams as $k => $v) {
            $st->bindValue($k, $v, PDO::PARAM_INT);
        }
        $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();

        return ['rows' => $st->fetchAll(), 'total' => $total];
    }

    /** Single post (respects is_hidden unless admin) */
    public static function one(int $postId, bool $isAdmin = false): ?array {
        $sql = "
          SELECT p.*, u.username, g.title AS game_title
          FROM posts p
          JOIN users u ON u.user_id = p.user_id
          LEFT JOIN games g ON g.game_id = p.game_id
          WHERE p.post_id = :id" . (!$isAdmin ? " AND p.is_hidden=0" : "") . "
          LIMIT 1
        ";
        $st = db()->prepare($sql);
        $st->execute([':id' => $postId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** Create a post */
    public static function create(int $userId, ?int $gameId, string $caption, string $imagePath): int {
        $sql = "INSERT INTO posts (user_id, game_id, caption, image_path, created_at)
                VALUES (:u, :g, :c, :p, NOW())";
        $st = db()->prepare($sql);
        $st->execute([
            ':u' => $userId,
            ':g' => $gameId ?: null,
            ':c' => $caption !== '' ? $caption : null,
            ':p' => $imagePath
        ]);
        return (int)db()->lastInsertId();
    }

    /** Like / Unlike */
    public static function like(int $userId, int $postId): void {
        $st = db()->prepare("INSERT IGNORE INTO post_likes (post_id, user_id, created_at)
                             VALUES (:p, :u, NOW())");
        $st->execute([':p' => $postId, ':u' => $userId]);
    }

    public static function unlike(int $userId, int $postId): void {
        $st = db()->prepare("DELETE FROM post_likes WHERE post_id = :p AND user_id = :u");
        $st->execute([':p' => $postId, ':u' => $userId]);
    }

    /** Comments listing (respects is_hidden unless admin) */
    public static function comments(int $postId, bool $isAdmin = false): array {
        $sql = "SELECT c.comment_id, c.body, c.created_at, c.is_hidden, u.username, u.user_id
                FROM post_comments c
                JOIN users u ON u.user_id = c.user_id
                WHERE c.post_id = :p " . (!$isAdmin ? "AND c.is_hidden=0" : "") . "
                ORDER BY c.created_at ASC, c.comment_id ASC";
        $st = db()->prepare($sql);
        $st->execute([':p' => $postId]);
        return $st->fetchAll();
    }

    /** Add a comment */
    public static function addComment(int $userId, int $postId, string $body, ?int $parentCommentId = null): void {
        $body = trim($body);
        if ($body === '') {
            throw new RuntimeException('A megjegyzés nem lehet üres.');
        }

        // If replying, validate that the parent comment belongs to the same post
        if ($parentCommentId !== null) {
            $stP = db()->prepare("SELECT post_id FROM post_comments WHERE comment_id = :cid LIMIT 1");
            $stP->execute([':cid' => $parentCommentId]);
            $parentPostId = $stP->fetchColumn();
            if (!$parentPostId) {
                throw new RuntimeException('A válaszolt hozzászólás nem található.');
            }
            if ((int)$parentPostId !== (int)$postId) {
                throw new RuntimeException('A válasz csak ugyanahhoz a poszthoz tartozhat.');
            }
        }

        $st = db()->prepare("
            INSERT INTO post_comments (post_id, user_id, body, parent_comment_id, created_at)
            VALUES (:p, :u, :b, :parent, NOW())
        ");
        $st->execute([
            ':p'      => $postId,
            ':u'      => $userId,
            ':b'      => $body,
            ':parent' => $parentCommentId,
        ]);
    }

    public static function commentsThreaded(int $postId, bool $isAdmin = false): array {
        // Fetch all visible comments for this post (or all for admin)
        $sql = "SELECT c.comment_id, c.post_id, c.user_id, c.body, c.is_hidden, c.parent_comment_id, c.created_at,
                    u.username
                FROM post_comments c
                JOIN users u ON u.user_id = c.user_id
                WHERE c.post_id = :p " . (!$isAdmin ? "AND c.is_hidden = 0" : "") . "
                ORDER BY c.created_at ASC, c.comment_id ASC";
        $st = db()->prepare($sql);
        $st->execute([':p' => $postId]);
        $rows = $st->fetchAll();

        // Group into parents and replies (one-level deep)
        $parents = [];
        $children = [];
        foreach ($rows as $r) {
            if (!empty($r['parent_comment_id'])) {
                $children[(int)$r['parent_comment_id']][] = $r;
            } else {
                $parents[] = $r;
            }
        }

        // Attach replies to parents
        foreach ($parents as &$p) {
            $pid = (int)$p['comment_id'];
            $p['replies'] = $children[$pid] ?? [];
        }
        unset($p);

        return $parents;
    }


    /** Moderation: hide/unhide post */
    public static function hidePost(int $postId, int $moderatorId, ?string $reason = null): void {
        $st = db()->prepare("UPDATE posts
                             SET is_hidden = 1, hidden_reason = :r, moderated_by = :m
                             WHERE post_id = :p");
        $st->execute([':r' => $reason, ':m' => $moderatorId, ':p' => $postId]);
    }

    public static function unhidePost(int $postId): void {
        $st = db()->prepare("UPDATE posts
                             SET is_hidden = 0, hidden_reason = NULL, moderated_by = NULL
                             WHERE post_id = :p");
        $st->execute([':p' => $postId]);
    }

    /** Moderation: hide/unhide comment */
    public static function hideComment(int $commentId): void {
        $st = db()->prepare("UPDATE post_comments SET is_hidden = 1 WHERE comment_id = :id");
        $st->execute([':id' => $commentId]);
    }

    public static function unhideComment(int $commentId): void {
        $st = db()->prepare("UPDATE post_comments SET is_hidden = 0 WHERE comment_id = :id");
        $st->execute([':id' => $commentId]);
    }
}
