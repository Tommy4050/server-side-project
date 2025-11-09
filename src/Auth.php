<?php
    final class Auth {
        public static function findUserByEmail(string $email): ?array {
            $sql = "SELECT user_id, username, email, password_hash, status
                        FROM users
                            WHERE email = :email
                            LIMIT 1";
            
            $stmt = db()->prepare($sql);
            $stmt->execute([':email' => $email]);
            $u = $stmt->fetch();
            return $u ?: null;
        }

        public static function findUserByUsername(string $username): ?array {
            $sql = "SELECT user_id, username, email, password_hash, status
                        FROM users
                            WHERE username = :u
                                LIMIT 1";
            $stmt = db()->prepare($sql);
            $stmt->execute([':u' => $username]);
            $u = $stmt->fetch();
            return $u ?: null;
        }

        public static function register(string $username, string $email, string $password): int {
            // Uniqueness checks (DB has UNIQUEs too)
            if (self::findUserByUsername($username)) {
                throw new RuntimeException('A felhasználónév már foglalt.');
            }
            if (self::findUserByEmail($email)) {
                throw new RuntimeException('Az e-mail cím már használatban van.');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (username, email, password_hash, status, created_at)
                        VALUES (:u, :e, :p, 'active', NOW())";
            $stmt = db()->prepare($sql);
            $stmt->execute([':u' => $username, ':e' => $email, ':p' => $hash]);

            return (int)db()->lastInsertId();
        }

        /*public static function login(string $email, string $password): array {
            $user = self::findUserByEmail($email);
            if (!$user || $user['status'] !== 'active') {
                throw new RuntimeException('Hibás hitelesítési adatok');
            }
            if (!password_verify($password, $user['password_hash'])) {
                throw new RuntimeException('Hibás hitelesítési adatok');
            }

            // Regenerate seesion to prevent fixation
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'user_id' => (int)$user['user_id'],
                'username' => $user['username'],
                'email' => $user['email'],
            ];
            return $_SESSION['user'];
        }*/
        public static function login(string $email, string $password): void {
            // Fetch user
            $st = db()->prepare("SELECT user_id, password_hash FROM users WHERE email = :e LIMIT 1");
            $st->execute([':e' => $email]);
            $u = $st->fetch();

            if (!$u || !password_verify($password, $u['password_hash'])) {
                throw new RuntimeException('Hibás e-mail vagy jelszó.');
            }

            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            session_regenerate_id(true);                // rotate ID once at login
            $_SESSION['user_id'] = (int)$u['user_id'];  // the one and only session flag
        }

        /*public static function logout(): void {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']);
            }
            session_destroy();
        }*/
        public static function logout(): void {
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
            }
            session_destroy();
        }

        /*public static function user(): ? array {
            return $_SESSION['user'] ?? null;
        }*/
        /*public static function user(): ?array {
            if (empty($_SESSION['user_id'])) return null;

            $st = db()->prepare("SELECT * FROM users WHERE user_id = :u LIMIT 1");
            $st->execute([':u' => (int)$_SESSION['user_id']]);
            $u = $st->fetch();
            if (!$u) { self::logout(); return null; }

            // Ban check (robust parsing)
            $val = $u['banned_until'] ?? null;
            if ($val && $val !== '0000-00-00 00:00:00') {
                $until = \DateTime::createFromFormat('Y-m-d H:i:s', $val) ?: new \DateTime($val);
                if ($until > new \DateTime()) {
                    self::logout();
                    return null;
                }
            }

            return $u;
        }*/
        public static function user(): ?array {
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            if (empty($_SESSION['user_id'])) return null;

            $st = db()->prepare("SELECT * FROM users WHERE user_id = :u LIMIT 1");
            $st->execute([':u' => (int)$_SESSION['user_id']]);
            $u = $st->fetch();

            if (!$u) { self::logout(); return null; }
            return $u;
        }


        public static function getUserWithHashById(int $userId): ?array {
            $sql = "SELECT user_id, username, email, password_hash, status
                    FROM users
                    WHERE user_id = :id
                    LIMIT 1";
            $st = db()->prepare($sql);
            $st->execute([':id' => $userId]);
            $u = $st->fetch();
            return $u ?: null;
        }

        public static function changePassword(int $userId, string $currentPassword, string $newPassword): void {
            $user = self::getUserWithHashById($userId);
            if (!$user || $user['status'] !== 'active') {
                throw new RuntimeException('Felhasználó nem található vagy inaktív.');
            }
            if (!password_verify($currentPassword, $user['password_hash'])) {
                throw new RuntimeException('A jelenlegi jelszó hibás.');
            }

            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

            $sql = "UPDATE users SET password_hash = :ph WHERE user_id = :id";
            $st = db()->prepare($sql);
            $st->execute([':ph' => $newHash, ':id' => $userId]);
        }

        public static function createPasswordReset(string $email, int $ttlMinutes = 60): ?string {
        // Look up user
        $user = self::findUserByEmail($email);
        if (!$user || $user['status'] !== 'active') {
            // Do NOT reveal whether the email exists; just return a fake token
            // But since we're "pretending", we can still return a token path for demo.
            // For security real apps return success without token.
        }

        $userId = (int)$user['user_id'];
        $rawToken = bin2hex(random_bytes(32)); // 64 hex chars

        // Hash with app key (bind token to this app)
        $app = require __DIR__ . '/../config/app.php';
        $key = $app['app_key'] ?? '';
        $tokenHash = hash('sha256', $rawToken . $key);

        // Invalidate previous active tokens for this user (optional but recommended)
        $sqlInvalidate = "UPDATE password_resets
                        SET used_at = NOW()
                        WHERE user_id = :uid AND used_at IS NULL AND expires_at > NOW()";
        $stI = db()->prepare($sqlInvalidate);
        $stI->execute([':uid' => $userId]);

        // Insert new reset
        $sql = "INSERT INTO password_resets (user_id, token_hash, expires_at)
                VALUES (:uid, :th, DATE_ADD(NOW(), INTERVAL :mins MINUTE))";
        $st = db()->prepare($sql);
        $st->execute([
            ':uid'  => $userId,
            ':th'   => $tokenHash,
            ':mins' => $ttlMinutes,
        ]);

        // Return the raw token (to embed in the reset link we will display)
        return $rawToken;
        }

        public static function getUserIdByResetToken(string $rawToken): ?int {
            if ($rawToken === '') return null;
            $app = require __DIR__ . '/../config/app.php';
            $key = $app['app_key'] ?? '';
            $tokenHash = hash('sha256', $rawToken . $key);

            $sql = "SELECT pr.user_id
                    FROM password_resets pr
                    WHERE pr.token_hash = :th
                    AND pr.used_at IS NULL
                    AND pr.expires_at > NOW()
                    LIMIT 1";
            $st = db()->prepare($sql);
            $st->execute([':th' => $tokenHash]);
            $row = $st->fetch();
            return $row ? (int)$row['user_id'] : null;
        }

        public static function consumeResetAndSetPassword(string $rawToken, string $newPassword): void {
            $app = require __DIR__ . '/../config/app.php';
            $key = $app['app_key'] ?? '';
            $tokenHash = hash('sha256', $rawToken . $key);

            // fetch the token first
            $sql = "SELECT id, user_id
                    FROM password_resets
                    WHERE token_hash = :th
                    AND used_at IS NULL
                    AND expires_at > NOW()
                    LIMIT 1";
            $st = db()->prepare($sql);
            $st->execute([':th' => $tokenHash]);
            $row = $st->fetch();
            if (!$row) {
                throw new RuntimeException('Érvénytelen vagy lejárt jelszó-visszaállítási kérelem.');
            }

            $userId = (int)$row['user_id'];
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

            // 1) update password
            $st1 = db()->prepare("UPDATE users SET password_hash = :ph WHERE user_id = :uid");
            $st1->execute([':ph' => $newHash, ':uid' => $userId]);

            // 2) mark token as used
            $st2 = db()->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = :id");
            $st2->execute([':id' => (int)$row['id']]);
        }
    }   
?>