<?php
require __DIR__ . '/../src/bootstrap.php';
$token = $_SESSION['reset_token_demo'] ?? null;
unset($_SESSION['reset_token_demo']);

$resetUrl = $token ? (base_url('reset_password.php') . '?token=' . urlencode($token)) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jelszó visszaállítás elkükldve</title>
</head>
<body>
    <h1>Ha létezik ilyen fiók, küldtünk egy e-mailt</h1>
    <p>Valós e-mail küldést most nem végzünk. Demó célból itt látod a „küldött” üzenetet:</p>

    <pre>
    Feladó: no-reply@példa.hu
    Címzett: &lt;te&gt;
    Tárgy: Jelszó visszaállítása

    Szia!

    Kattints az alábbi linkre a jelszavad visszaállításához (60 percig érvényes):

    <?= e($resetUrl ?: '[nincs token a demóhoz]') ?>


    Ha nem te kérted, hagyd figyelmen kívül ezt az üzenetet.
    </pre>

    <p>
        <a href="<?= e(base_url('login.php')) ?>">Vissza a bejelentkezéshez</a>
    </p>
</body>
</html>