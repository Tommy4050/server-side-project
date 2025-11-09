<?php
require __DIR__ . '/../src/bootstrap.php';

if (!isset($_SESSION['counter'])) $_SESSION['counter'] = 0;
$_SESSION['counter']++;

echo "<pre>";
echo "Session ID: " . session_id() . PHP_EOL;
echo "Counter: " . $_SESSION['counter'] . PHP_EOL;

$u = Auth::user();
echo "User: " . ($u ? ($u['username'].' #'.$u['user_id']) : 'GUEST') . PHP_EOL;

var_dump(session_get_cookie_params());
echo "</pre>";
