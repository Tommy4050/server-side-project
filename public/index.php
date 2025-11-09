<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<?php require __DIR__ . '/../src/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kezdőlap</title>
</head>
<body>
    <h1>Gamebay - Főoldal</h1>

    <?php if ($u = Auth::user()): ?>
        <p>Bejelentkezve mint <strong><?= e($u['username']) ?></strong></p>
        <p>
            <a href="<?= e(base_url('library.php')) ?>">Saját könyvtár</a> |
            <a href="<?= e(base_url('profile.php')) ?>">Profil</a> |
            <a href="<?= e(base_url('logout.php')) ?>">Kijelenkezés</a>
        </p>
    <?php else: ?>
        <p>
            <a href="<?= e(base_url('login.php')) ?>">Bejelentkezés</a> 
                vagy 
            <a href="<?= e(base_url('register.php')) ?>">Regisztráció</a>
        </p>
    <?php endif; ?>
</body>
</html>