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
    <title>Bejelentkezés</title>
</head>
<body>
    <h1>Bejelntkezés</h1>

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