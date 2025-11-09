<?php
require __DIR__ . '/../src/bootstrap.php';

$token = $_GET['token'] ?? '';
$errors = [];
$notice = '';

$userId = Auth::getUserIdByResetToken((string)$token);
if (!$userId) {
    $errors[] = 'Érvénytelen vagy lejárt visszaállítási link.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors) {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $errors[] = 'Érvénytelen űrlap token.';
    }

    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['new_password_confirm'] ?? '';

    if (strlen($new) < 6) $errors[] = 'Az új jelszó legalább 6 karakter.';
    if ($new !== $confirm) $errors[] = 'Az új jelszó és megerősítése nem egyezik.';

    if (!$errors) {
        try {
            Auth::consumeResetAndSetPassword((string)$token, $new);
            // Optionally force login
            $notice = 'A jelszavad frissítve. Most már bejelentkezhetsz az új jelszóval.';
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Új jelszó beállítása</title>
</head>
<body>
    <h1>Új jelszó beállítása</h1>

    <?php if ($notice): ?>
        <div><strong><?= e($notice) ?></strong></div>
        <p>
            <a href="<?= e(base_url('login.php')) ?>">Tovább a bejelentkezéshez</a>
        </p>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div>
            <h2>Hibák:</h2>
            <ul>
                <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!$notice && $userId): ?>
    <form method="post" action="">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

        <div>
            <label for="new_password">Új jelszó</label><br>
            <input id="new_password" name="new_password" type="password" required>
        </div>

        <div>
            <label for="new_password_confirm">Új jelszó megerősítése</label><br>
            <input id="new_password_confirm" name="new_password_confirm" type="password" required>
        </div>

        <button type="submit">Jelszó beállítása</button>
    </form>
    <?php endif; ?>

    <p><a href="<?= e(base_url('login.php')) ?>">Vissza a bejelentkezéshez</a></p>
</body>
</html>