<?php
    // Start session and load config
    session_start();

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
?>