<?php

final class Library
{
    public static function allGenres(): array {
        $sql = "SELECT genre_id, name FROM genres ORDER BY name ASC";
        return db()->query($sql)->fetchAll();
    }

    public static function getUserLibrary(
        int $userId,
        ?string $titleLike = null,
        ?int $genreId = null,
        ?float $priceMin = null,
        ?float $priceMax = null,
        int $page = 1,
        int $perPage = 10
    ): array {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        // Base WHERE for user
        $where = ["l.user_id = :uid"];
        $params = [':uid' => $userId];

        if ($titleLike !== null && $titleLike !== '') {
            $where[] = "g.title LIKE :t";
            $params[':t'] = '%' . $titleLike . '%';
        }
        if ($priceMin !== null && $priceMin !== '') {
            $where[] = "g.price >= :pmin";
            $params[':pmin'] = $priceMin;
        }
        if ($priceMax !== null && $priceMax !== '') {
            $where[] = "g.price <= :pmax";
            $params[':pmax'] = $priceMax;
        }

        // Genre filter via EXISTS (so it doesn’t break the GROUP BY)
        $genreExistsSql = '';
        if ($genreId !== null && $genreId > 0) {
            $genreExistsSql = " AND EXISTS (
                SELECT 1
                  FROM game_genres x
                 WHERE x.game_id = g.game_id
                   AND x.genre_id = :gid
            )";
            $params[':gid'] = $genreId;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Count (with EXISTS if needed)
        $countSql = "
            SELECT COUNT(*) AS c
              FROM libraries l
              JOIN games g ON g.game_id = l.game_id
            $whereSql
            $genreExistsSql
        ";
        $stCount = db()->prepare($countSql);
        $stCount->execute($params);
        $total = (int)$stCount->fetchColumn();

        // Page data (with genres)
        $sql = "
            SELECT
              g.game_id,
              g.title,
              g.price,
              g.image_url,
              g.publisher,
              l.acquired_at,
              l.source,
              GROUP_CONCAT(DISTINCT ge.name ORDER BY ge.name SEPARATOR ', ') AS genres
            FROM libraries l
            JOIN games g ON g.game_id = l.game_id
            LEFT JOIN game_genres gg ON gg.game_id = g.game_id
            LEFT JOIN genres ge ON ge.genre_id = gg.genre_id
            $whereSql
            $genreExistsSql
            GROUP BY g.game_id, g.title, g.price, g.image_url, g.publisher, l.acquired_at, l.source
            ORDER BY l.acquired_at DESC, g.title ASC
            LIMIT :limit OFFSET :offset
        ";

        $st = db()->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();

        return [
            'rows'  => $st->fetchAll(),
            'total' => $total,
        ];
    }
}
