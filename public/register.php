<?php
require __DIR__ . '/../src/bootstrap.php';

$errors = [];
$old = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $errors[] = 'Érvénytelen űrlap token. Frissítsd az oldalt és próbáld újra.';
    }

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $pass2    = $_POST['password_confirm'] ?? '';
    $old = ['username' => $username, 'email' => $email];

    if ($username === '' || strlen($username) < 3) $errors[] = 'A felhasználónév legalább 3 karakter.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Érvénytelen e-mail cím.';
    if (strlen($pass) < 6) $errors[] = 'A jelszó legalább 6 karakter.';
    if ($pass !== $pass2) $errors[] = 'A jelszavak nem egyeznek.';

    if (!$errors) {
        try {
            $userId = Auth::register($username, $email, $pass);
            // Auto-login after registration (optional):
            Auth::login($email, $pass);
            redirect(base_url('index.php'));
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
    <title>Regisztráció</title>
</head>
<body>
    <h1>Regisztráció</h1>

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
            <label for="username">Felhasználónév</label><br>
            <input id="username" name="username" type="text" required value="<?= e($old['username']) ?>">
        </div>

        <div>
            <label for="email">E-mail</label><br>
            <input id="email" name="email" type="email" required value="<?= e($old['email']) ?>">
        </div>

        <div>
            <label for="password">Jelszó</label><br>
            <input id="password" name="password" type="password" required>
        </div>

        <div>
            <label for="password_confirm">Jelszó megerősítése</label><br>
            <input id="password_confirm" name="password_confirm" type="password" required>
        </div>

        <button type="submit">Regisztráció</button>
    </form>

    <p>Van már fiókod? <a href="<?= e(base_url('login.php')) ?>">Bejelentkezés</a></p>
</body>
</html>