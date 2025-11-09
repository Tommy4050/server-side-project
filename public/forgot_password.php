<?php
require __DIR__ . '/../src/bootstrap.php';

$errors = [];
$link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $errors[] = 'Érvénytelen űrlap token.';
    }
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Érvénytelen e-mail cím.';
    }

    if (!$errors) {
        try {
            $token = Auth::createPasswordReset($email, 60); // 60 perc
            // In a real app we would *not* reveal the token or even if the email exists.
            // For demo, we pass it to the "sent" page to display the pretend email.
            $_SESSION['reset_token_demo'] = $token; // store briefly
            header('Location: ' . base_url('forgot_password_sent.php'));
            exit;
        } catch (Throwable $e) {
            // Still redirect to the "sent" page to avoid user enumeration
            $_SESSION['reset_token_demo'] = null;
            header('Location: ' . base_url('forgot_password_sent.php'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elfelejtett jelszó</title>
</head>
<body>
    <h1>Elfelejtett jelszó</h1>

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
            <label for="email">E-mail címed</label><br>
            <input id="email" name="email" type="email" required>
        </div>
        <button type="submit">Jelszó visszaállítás kérése</button>
    </form>

    <p><a href="<?= e(base_url('login.php')) ?>">Vissza a bejelentkezéshez</a></p>
</body>
</html>