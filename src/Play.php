<?php
final class Play
{
    public static function activeSessionForUser(int $userId): ?array {
        $st = db()->prepare("
            SELECT session_id, user_id, game_id, started_at
            FROM play_sessions
            WHERE user_id = :u AND ended_at IS NULL
            LIMIT 1
        ");
        $st->execute([':u'=>$userId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function start(int $userId, int $gameId): int {
        db()->beginTransaction();
        try {
            // make sure the user owns the game
            $own = db()->prepare("SELECT 1 FROM libraries WHERE user_id=:u AND game_id=:g LIMIT 1");
            $own->execute([':u'=>$userId, ':g'=>$gameId]);
            if (!$own->fetchColumn()) {
                throw new RuntimeException('Ez a játék nincs a könyvtáradban.');
            }

            // one open session per user
            $open = self::activeSessionForUser($userId);
            if ($open && (int)$open['game_id'] !== $gameId) {
                throw new RuntimeException('Már fut egy másik játék. Előbb állítsd le azt.');
            }
            if ($open && (int)$open['game_id'] === $gameId) {
                db()->commit();
                return (int)$open['session_id'];
            }

            $st = db()->prepare("
                INSERT INTO play_sessions (user_id, game_id, started_at)
                VALUES (:u, :g, NOW())
            ");
            $st->execute([':u'=>$userId, ':g'=>$gameId]);
            $id = (int)db()->lastInsertId();

            db()->commit();
            return $id;
        } catch (Throwable $e) {
            db()->rollBack();
            throw $e;
        }
    }

    public static function stop(int $userId, int $gameId): void {
        db()->beginTransaction();
        try {
            $st = db()->prepare("
                SELECT session_id, started_at, game_id
                FROM play_sessions
                WHERE user_id=:u AND ended_at IS NULL
                LIMIT 1 FOR UPDATE
            ");
            $st->execute([':u'=>$userId]);
            $row = $st->fetch();
            if (!$row) {
                throw new RuntimeException('Nincs futó játék.');
            }
            if ((int)$row['game_id'] !== $gameId) {
                throw new RuntimeException('Másik játék fut. Előbb állítsd le azt.');
            }

            // finalize session
            $upd = db()->prepare("
                UPDATE play_sessions
                SET ended_at = NOW(),
                    duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW())
                WHERE session_id = :id
            ");
            $upd->execute([':id' => (int)$row['session_id']]);

            // add to cumulative total in libraries
            $add = db()->prepare("
                UPDATE libraries
                SET total_play_seconds = total_play_seconds + (
                    SELECT COALESCE(duration_seconds,0) FROM play_sessions WHERE session_id = :id
                )
                WHERE user_id = :u AND game_id = :g
            ");
            $add->execute([
                ':id' => (int)$row['session_id'],
                ':u'  => $userId,
                ':g'  => $gameId
            ]);

            db()->commit();
        } catch (Throwable $e) {
            db()->rollBack();
            throw $e;
        }
    }
}
