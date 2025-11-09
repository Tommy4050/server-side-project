<?php
require __DIR__ . '/../src/bootstrap.php';

$me = Auth::user();
if (!$me) {
    redirect(base_url('login.php'));
}

$errors = [];
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $errors[] = 'Érvénytelen űrlap token. Frissítsd az oldalt és próbáld újra.';
    }

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['new_password_confirm'] ?? '';

    // Basic validations
    if ($current === '') $errors[] = 'Add meg a jelenlegi jelszót.';
    if (strlen($new) < 6) $errors[] = 'Az új jelszó legalább 6 karakter.';
    if ($new !== $confirm) $errors[] = 'Az új jelszó és a megerősítés nem egyezik.';

    // Optional: prevent reusing the same password
    if ($new !== '' && $current !== '' && hash_equals($current, $new)) {
        $errors[] = 'Az új jelszó nem lehet azonos a jelenlegi jelszóval.';
    }

    if (!$errors) {
        try {
            Auth::changePassword((int)$me['user_id'], $current, $new);

            // Option A: keep user logged in
            $notice = 'A jelszó sikeresen frissítve.';

            // Option B (recommended): force re-login after password change
            // Auth::logout();
            // redirect(base_url('login.php'));

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
    <title>Jelszó módosítás</title>
</head>
<body>
    <h1>Jelszó módosítása</h1>

    <p>
        <a href="<?= e(base_url('index.php')) ?>">← Főoldal</a> |
        <a href="<?= e(base_url('profile.php')) ?>">Profil</a>
    </p>

    <?php if ($notice): ?>
        <div><strong><?= e($notice) ?></strong></div>
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

    <form method="post" action="">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

        <div>
        <label for="current_password">Jelenlegi jelszó</label><br>
        <input id="current_password" name="current_password" type="password" required>
        </div>

        <div>
        <label for="new_password">Új jelszó</label><br>
        <input id="new_password" name="new_password" type="password" required>
        </div>

        <div>
        <label for="new_password_confirm">Új jelszó megerősítése</label><br>
        <input id="new_password_confirm" name="new_password_confirm" type="password" required>
        </div>

        <button type="submit">Jelszó frissítése</button>
    </form>
</body>
</html>