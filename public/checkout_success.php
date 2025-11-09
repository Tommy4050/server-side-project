<?php
require __DIR__ . '/../src/bootstrap.php';
$u = Auth::user();
if (!$u) redirect(base_url('login.php'));

$orderId = (int)($_GET['order_id'] ?? 0);
$title = 'Sikeres rendelés';
$active = 'store';
include __DIR__ . '/partials/header.php';

$sidebarTitle = 'Következő lépések';
ob_start();
?>
<div>
  <p><a href="<?= e(base_url('library.php')) ?>">Ugrás a könyvtáramhoz</a></p>
  <p><a href="<?= e(base_url('store.php')) ?>">Vissza az áruházba</a></p>
</div>
<?php
$sidebarContent = ob_get_clean();
include __DIR__ . '/partials/sidebar-filters.php';
?>

<header class="content__header">
  <h1 class="content__title">Köszönjük a vásárlást!</h1>
</header>

<p>Rendelésszám: <strong>#<?= (int)$orderId ?></strong></p>
<p>A megvásárolt játékokat hozzáadtuk a <a href="<?= e(base_url('library.php')) ?>">könyvtáradhoz</a>.</p>

<?php include __DIR__ . '/partials/footer.php'; ?>
