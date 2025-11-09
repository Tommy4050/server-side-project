<?php
require __DIR__ . '/../src/bootstrap.php';

$me = Auth::user();
if (!$me) redirect(base_url('login.php'));
$userId = (int)$me['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf'] ?? null)) {
    redirect(base_url('friends.php'));
}

$action = $_POST['action'] ?? '';
$other  = isset($_POST['other_user_id']) ? (int)$_POST['other_user_id'] : 0;

try {
    switch ($action) {
        case 'send':
            Friend::sendRequest($userId, $other);
            break;
        case 'cancel':
            Friend::cancelSent($userId, $other);
            break;
        case 'accept':
            Friend::acceptReceived($userId, $other);
            break;
        case 'decline':
            Friend::declineReceived($userId, $other);
            break;
        case 'unfriend':
            Friend::unfriend($userId, $other);
            break;
    }
} catch (Throwable $e) {
    // You can store a flash message if you have a system; for now we ignore.
}

$redirect = $_POST['redirect'] ?? base_url('friends.php');
header('Location: ' . $redirect);
exit;
