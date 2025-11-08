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

        public static function login(string $email, string $password): array {
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
        }

        public static function logout(): void {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']);
            }
            session_destroy();
        }

        public static function user(): ? array {
            return $_SESSION['user'] ?? null;
        }
    }
?>