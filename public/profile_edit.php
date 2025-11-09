<?php
require __DIR__ . '/../src/bootstrap.php';

$me = Auth::user();
if (!$me) {
    redirect(base_url('login.php'));
}

$COUNTRIES_BY_CONTINENT = require __DIR__ . '/../src/countries.php';
$ALL_COUNTRIES = [];
foreach ($COUNTRIES_BY_CONTINENT as $group => $items) {
    foreach ($items as $code => $name) {
        $ALL_COUNTRIES[$code] = $name;
    }
}

$errors = [];
$current = User::getById((int)$me['user_id']);
if (!$current) {
    $errors[] = 'A felhasználó nem található.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $current) {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $errors[] = 'Érvénytelen űrlap token. Frissítsd az oldalt és próbáld újra.';
    }

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');

    $billing_full_name   = trim($_POST['billing_full_name'] ?? '');
    $billing_address1    = trim($_POST['billing_address1'] ?? '');
    $billing_address2    = trim($_POST['billing_address2'] ?? '');
    $billing_city        = trim($_POST['billing_city'] ?? '');
    $billing_postal_code = trim($_POST['billing_postal_code'] ?? '');
    $billing_country     = strtoupper(trim($_POST['billing_country'] ?? ''));

    if ($username === '' || strlen($username) < 3) $errors[] = 'A felhasználónév legalább 3 karakter.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Érvénytelen e-mail cím.';
    if ($billing_country !== '' && !array_key_exists($billing_country, $ALL_COUNTRIES)) {
        $errors[] = 'Érvénytelen országkód.';
    }

    if (!$errors) {
        if (strcasecmp($username, $current['username']) !== 0 && Auth::findUserByUsername($username)) {
            $errors[] = 'A felhasználónév már foglalt.';
        }
        if (strcasecmp($email, $current['email']) !== 0 && Auth::findUserByEmail($email)) {
            $errors[] = 'Az e-mail cím már használatban van.';
        }
    }

    if (!$errors) {
        try {
            User::updateProfile((int)$me['user_id'], [
                'username'            => $username,
                'email'               => $email,
                'billing_full_name'   => $billing_full_name,
                'billing_address1'    => $billing_address1,
                'billing_address2'    => $billing_address2,
                'billing_city'        => $billing_city,
                'billing_postal_code' => $billing_postal_code,
                'billing_country'     => $billing_country,
            ]);

            $_SESSION['user']['username'] = $username;
            $_SESSION['user']['email']    = $email;

            // Redirect to read-only profile page with success flag
            redirect(base_url('profile.php?updated=1'));
        } catch (Throwable $e) {
            $errors[] = 'Nem sikerült frissíteni a profilt: ' . $e->getMessage();
        }
    } else {
        // If validation failed, refresh $current from posted values to re-populate the form
        $current = array_merge($current, [
            'username'            => $username,
            'email'               => $email,
            'billing_full_name'   => $billing_full_name,
            'billing_address1'    => $billing_address1,
            'billing_address2'    => $billing_address2,
            'billing_city'        => $billing_city,
            'billing_postal_code' => $billing_postal_code,
            'billing_country'     => $billing_country,
        ]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil szerkesztése</title>
</head>
<body>
    <h1>Profil szerkesztése</h1>

    <p>
        <a href="<?= e(base_url('profile.php')) ?>">← Vissza a profilhoz</a>
    </p>

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

    <?php if ($current): ?>
    <form method="post" action="">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

        <fieldset>
        <legend>Bejelentkezési adatok</legend>
        <div>
            <label for="username">Felhasználónév</label><br>
            <input id="username" name="username" type="text" required value="<?= e($current['username'] ?? '') ?>">
        </div>

        <div>
            <label for="email">E-mail</label><br>
            <input id="email" name="email" type="email" required value="<?= e($current['email'] ?? '') ?>">
        </div>
        </fieldset>

        <fieldset>
        <legend>Számlázási adatok</legend>

        <div>
            <label for="billing_full_name">Név</label><br>
            <input id="billing_full_name" name="billing_full_name" type="text" value="<?= e($current['billing_full_name'] ?? '') ?>">
        </div>

        <div>
            <label for="billing_address1">Cím 1</label><br>
            <input id="billing_address1" name="billing_address1" type="text" value="<?= e($current['billing_address1'] ?? '') ?>">
        </div>

        <div>
            <label for="billing_address2">Cím 2</label><br>
            <input id="billing_address2" name="billing_address2" type="text" value="<?= e($current['billing_address2'] ?? '') ?>">
        </div>

        <div>
            <label for="billing_city">Város</label><br>
            <input id="billing_city" name="billing_city" type="text" value="<?= e($current['billing_city'] ?? '') ?>">
        </div>

        <div>
            <label for="billing_postal_code">Irányítószám</label><br>
            <input id="billing_postal_code" name="billing_postal_code" type="text" value="<?= e($current['billing_postal_code'] ?? '') ?>">
        </div>

        <div>
            <label for="billing_country">Ország</label><br>
            <select id="billing_country" name="billing_country">
                <option value="">-- Válassz országot --</option>
                <?php
                    $selected = strtoupper((string)($current['billing_country'] ?? ''));
                    foreach ($COUNTRIES_BY_CONTINENT as $continent => $countries):
                ?>
                    <optgroup label="<?= e($continent) ?>">
                    <?php foreach ($countries as $code => $name): ?>
                        <option value="<?= e($code) ?>"<?= ($selected === $code ? ' selected' : '') ?>>
                        <?= e($name) ?> (<?= e($code) ?>)
                        </option>
                    <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </div>
        </fieldset>

        <button type="submit">Mentés</button>
    </form>
    <?php endif; ?>
</body>
</html>