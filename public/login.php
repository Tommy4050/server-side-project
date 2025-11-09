<?php
require __DIR__ . '/../src/bootstrap.php';

$errors = [];
$old = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $errors[] = 'Érvénytelen űrlap token. Frissítsd az oldalt és próbáld újra.';
    }

    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $old['email'] = $email;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Érvénytelen e-mail cím.';
    if ($pass === '') $errors[] = 'Add meg a jelszót.';

    if (!$errors) {
        try {
            // Fetch user first so we can check the ban BEFORE logging them in
            $st = db()->prepare("SELECT user_id, email, password_hash, banned_until, ban_reason FROM users WHERE email = :e LIMIT 1");
            $st->execute([':e' => $email]);
            $u = $st->fetch();

            if (!$u || !password_verify($pass, $u['password_hash'])) {
                $errors[] = 'Hibás e-mail vagy jelszó.';
            } else {
                // Robust ban window check (handles null/zero dates)
                $val = $u['banned_until'] ?? null;
                $stillBanned = false;
                $until = null;
                if ($val && $val !== '0000-00-00 00:00:00') {
                    $until = DateTime::createFromFormat('Y-m-d H:i:s', $val) ?: new DateTime($val);
                    $stillBanned = ($until > new DateTime());
                }

                if ($stillBanned) {
                    $untilFmt = $until->format('Y-m-d H:i');
                    $reason = $u['ban_reason'] ?: 'Nincs megadva indok.';
                    $errors[] = "A fiók ideiglenesen tiltva van $untilFmt időpontig. Indok: $reason";
                } else {
                    // Delegate to your existing login helper (sets session, etc.)
                    Auth::login($email, $pass);
                    redirect(base_url('index.php'));
                }
            }
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
    <title>Bejelentkezés</title>
</head>
<body>
    <h1>Bejelentkezés</h1>

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
        <label for="email">E-mail</label><br>
        <input id="email" name="email" type="email" required value="<?= e($old['email']) ?>">
      </div>

      <div>
        <label for="password">Jelszó</label><br>
        <input id="password" name="password" type="password" required>
      </div>

      <button type="submit">Belépés</button>
    </form>

    <p>Nincs még fiókod? <a href="<?= e(base_url('register.php')) ?>">Regisztráció</a></p>
    <p><a href="<?= e(base_url('forgot_password.php')) ?>">Elfelejtetted a jelszavad?</a></p>
</body>
</html>
