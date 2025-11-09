<?php
require __DIR__ . '/../src/bootstrap.php';

$u = Auth::user();
if (!$u) redirect(base_url('login.php'));

$errors = [];
$notice = '';

$data = Cart::getActiveCart((int)$u['user_id']);
$rows = $data['rows'];
$total = (float)$data['total'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $errors[] = 'Érvénytelen űrlap token.';
    } else {
        try {
            if (!$rows) throw new RuntimeException('A kosár üres.');
            $orderId = OrderModel::checkout((int)$u['user_id']);
            // Success page
            header('Location: ' . base_url('checkout_success.php') . '?order_id=' . (int)$orderId);
            exit;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$title = 'Fizetés';
$active = 'store';
include __DIR__ . '/partials/header.php';

$sidebarTitle = 'Összegzés';
ob_start();
?>
<div>
  <p><strong>Végösszeg:</strong> <?= number_format($total, 2, '.', ' ') ?> Ft</p>
  <p><a href="<?= e(base_url('cart.php')) ?>">← Vissza a kosárhoz</a></p>
</div>
<?php
$sidebarContent = ob_get_clean();
include __DIR__ . '/partials/sidebar-filters.php';
?>

<header class="content__header">
  <h1 class="content__title">Rendelés ellenőrzése</h1>
</header>

<?php if ($errors): ?>
  <div>
    <h3>Hibák:</h3>
    <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<?php if (!$rows): ?>
  <div class="empty">
    <p>A kosarad üres.</p>
    <p><a href="<?= e(base_url('store.php')) ?>">Vissza az áruházhoz</a></p>
  </div>
<?php else: ?>
  <table border="1" cellpadding="6" cellspacing="0">
    <thead>
      <tr>
        <th>Játék</th>
        <th>Menny.</th>
        <th>Egységár</th>
        <th>Sorösszeg</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['title'] ?? '—') ?></td>
          <td><?= (int)$r['quantity'] ?></td>
          <td><?= number_format((float)$r['unit_price_at_add'], 2, '.', ' ') ?> Ft</td>
          <td><?= number_format((float)$r['line_total'], 2, '.', ' ') ?> Ft</td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="3" align="right"><strong>Összesen:</strong></td>
        <td><strong><?= number_format($total, 2, '.', ' ') ?> Ft</strong></td>
      </tr>
    </tfoot>
  </table>

  <h2>Számlázási adatok</h2>
  <?php
    $userFull = User::getById((int)$u['user_id']);
    $missing = [];
    foreach (['billing_full_name','billing_address1','billing_city','billing_postal_code','billing_country'] as $k) {
      if (empty(trim((string)($userFull[$k] ?? '')))) $missing[] = $k;
    }
  ?>
  <?php if ($missing): ?>
    <p><strong>Figyelem:</strong> Hiányos számlázási adatok. Lépj a
      <a href="<?= e(base_url('profile_edit.php')) ?>">profil szerkesztés</a> oldalra a javításhoz.</p>
  <?php else: ?>
    <p><?= e($userFull['billing_full_name']) ?><br>
       <?= e($userFull['billing_address1']) ?> <?= e($userFull['billing_address2'] ?? '') ?><br>
       <?= e($userFull['billing_postal_code']) ?> <?= e($userFull['billing_city']) ?>, <?= e(strtoupper($userFull['billing_country'])) ?></p>
  <?php endif; ?>

  <form method="post" action="">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <button type="submit"<?= $missing ? ' disabled' : '' ?>>Vásárlás befejezése</button>
  </form>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
