<?php
require __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Play.php';

$me = Auth::user();
if (!$me) redirect(base_url('login.php'));

$errors = [];
try {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        throw new RuntimeException('Érvénytelen űrlap token.');
    }
    $action = $_POST['action'] ?? '';
    $gameId = (int)($_POST['game_id'] ?? 0);
    if ($gameId <= 0) throw new RuntimeException('Hiányzik a játék azonosító.');

    if ($action === 'start') {
        // ensure ownership
        $own = db()->prepare("SELECT 1 FROM libraries WHERE user_id=:u AND game_id=:g LIMIT 1");
        $own->execute([':u'=>(int)$me['user_id'], ':g'=>$gameId]);
        if (!$own->fetchColumn()) throw new RuntimeException('Ez a játék nincs a könyvtáradban.');

        Play::start((int)$me['user_id'], $gameId);

    } elseif ($action === 'stop') {
        Play::stop((int)$me['user_id'], $gameId);

    } else {
        throw new RuntimeException('Ismeretlen művelet.');
    }

} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}

// Redirect back to library with optional error
$to = base_url('library.php');
if ($errors) {
    $to .= '?error=' . urlencode(implode('; ', $errors));
}
header('Location: ' . $to);
exit;
