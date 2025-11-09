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

?>