<?php
require __DIR__ . '/../src/bootstrap.php';

$me = Auth::user();
if (!$me) {
    redirect(base_url('login.php'));
}

$user = User::getById((int)$me['user_id']);
$updated = isset($_GET['updated']) ? true : false;

function show_or_missing(?string $v): string {
    $v = trim((string)$v);
    return $v !== '' ? htmlspecialchars($v, ENT_QUOTES, 'UTF-8') : '<em>Nincs megadva</em>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil</title>
</head>
<body>
    <h1>Felhasználói profil</h1>

  <p>
    <a href="<?= e(base_url('index.php')) ?>">← Főoldal</a> |
    <a href="<?= e(base_url('change_password.php')) ?>">Jelszó módosítása</a> |
    <a href="<?= e(base_url('logout.php')) ?>">Kijelentkezés</a>
  </p>

  <?php if ($updated): ?>
    <div><strong>Profil sikeresen frissítve.</strong></div>
  <?php endif; ?>

  <?php if (!$user): ?>
    <p>Felhasználó nem található.</p>
  <?php else: ?>
    <section>
      <h2>Bejelentkezési adatok</h2>
      <p><strong>Felhasználónév:</strong> <?= show_or_missing($user['username'] ?? '') ?></p>
      <p><strong>E-mail:</strong> <?= show_or_missing($user['email'] ?? '') ?></p>
    </section>

    <section>
      <h2>Számlázási adatok</h2>
      <p><strong>Név:</strong> <?= show_or_missing($user['billing_full_name'] ?? '') ?></p>
      <p><strong>Cím 1:</strong> <?= show_or_missing($user['billing_address1'] ?? '') ?></p>
      <p><strong>Cím 2:</strong> <?= show_or_missing($user['billing_address2'] ?? '') ?></p>
      <p><strong>Város:</strong> <?= show_or_missing($user['billing_city'] ?? '') ?></p>
      <p><strong>Irányítószám:</strong> <?= show_or_missing($user['billing_postal_code'] ?? '') ?></p>
      <p><strong>Ország:</strong>
        <?php
          $code = strtoupper((string)($user['billing_country'] ?? ''));
          if ($code === '') {
              echo '<em>Nincs megadva</em>';
          } else {
              echo e($code);
          }
        ?>
      </p>

      <?php
        $missing = [];
        foreach (['billing_full_name','billing_address1','billing_city','billing_postal_code','billing_country'] as $k) {
          if (empty(trim((string)($user[$k] ?? '')))) $missing[] = $k;
        }
      ?>
      <?php if ($missing): ?>
        <p><strong>Figyelem:</strong> Néhány számlázási adat hiányzik. Kérjük, egészítsd ki!</p>
      <?php endif; ?>
    </section>

    <p>
      <a href="<?= e(base_url('profile_edit.php')) ?>">Adatok szerkesztése</a>
    </p>
  <?php endif; ?>
</body>
</html>