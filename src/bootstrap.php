<?php
    // Start session and load config
    // session_start();

// ===== Session bootstrap (single source of truth) =====
if (session_status() !== PHP_SESSION_ACTIVE) {
    // Use a stable cookie name for the app
    session_name('gamebay');

    // Only mark cookie "secure" when actually on HTTPS
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    // Site-wide cookie path so *all* pages see it
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',      // IMPORTANT: not a subpath
        'domain'   => '',       // default host
        'secure'   => $secure,  // false on http://localhost
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    // (Optional) ensure PHP has a writable session folder (XAMPP normally ok)
    // ini_set('session.save_path', '/Applications/XAMPP/xamppfiles/temp');

    session_start();
}
// ===== End session bootstrap =====


    $app = require __DIR__ . '/../config/app.php';
    $db = require __DIR__ . '/../config/database.php';

    date_default_timezone_set($app['timezone'] ?? 'UTC');

    // Error display policy
    if(($app['env'] ?? 'prod') === 'dev') {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    } else {
        ini_set('display_errors', 0);
        error_reporting(E_ALL & ~E_NOTICE & E_DEPRECATED & ~E_STRICT);
    }

    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/Auth.php';
    require_once __DIR__ . '/User.php';
    require_once __DIR__ . '/Library.php';
    require_once __DIR__ . '/Cart.php';
    require_once __DIR__ . '/Order.php';
    require_once __DIR__ . '/Post.php';
    require_once __DIR__ . '/Friend.php';

?>