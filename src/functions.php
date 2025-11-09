<?php
    function base_url(string $path = ''): string {
        // If app.php specifies a base_url, prefer it
        $app = require __DIR__ . '/../config/app.php';
        $cfgBase = trim($app['base_url'] ?? '', '/');

        if ($cfgBase !== '') {
            $base = '/' . $cfgBase;
        } else {
            // Auto-detect relative to the current script's directory (usually /.../public)
            $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            $base = ($scriptDir === '') ? '' : $scriptDir;
        }

        $path = ltrim($path, '/');
        return $base . ($path ? "/$path" : '');
    }

    function asset_url(string $path): string {
        // if it's already an absolute URL, return as-is
        if (preg_match('~^https?://~i', $path)) return $path;
        return base_url(ltrim($path, '/'));
    }



    function redirect(string $to): never {
        header('Location: ' . $to);
        exit;
    }

    function e(string $str): string {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }

    function csrf_token():string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    function verify_csrf(?string $token): bool {
        return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    function require_login(): array {
        $u = Auth::user();
        if (!$u) {
            redirect(base_url('login.php'));
        }
        return $u;
    }

    function is_admin(): bool {
        $u = Auth::user();
        if (!$u) return false;
        // Load fresh from DB in case session is stale:
        $st = db()->prepare("SELECT is_admin FROM users WHERE user_id = :u LIMIT 1");
        $st->execute([':u' => (int)$u['user_id']]);
        return (bool)$st->fetchColumn();
    }

    function require_admin(): void {
        if (!is_admin()) {
            http_response_code(403);
            echo "<!doctype html><meta charset='utf-8'><h1>403 – Hozzáférés megtagadva</h1><p>Admin jogosultság szükséges.</p>";
            exit;
        }
    }

    function is_banned(array $user): bool {
        $val = $user['banned_until'] ?? null;
        if (!$val || $val === '0000-00-00 00:00:00') return false;
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $val) ?: new DateTime($val);
        return $dt > new DateTime();
    }


?>