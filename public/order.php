<?php
require __DIR__ . '/../src/bootstrap.php';

$u = Auth::user();
if (!$u) redirect(base_url('login.php'));

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order   = $orderId ? OrderModel::getOne((int)$u['user_id'], $orderId) : null;

if (!$order) {
    http_response_code(404);
    echo "<!doctype html><meta charset='utf-8'><h1>404 – A rendelés nem található</h1>";
    exit;
}

$title  = 'Rendelés részletei';
$active = 'store';

ob_start(); ?>
<div>
  <p><a href="<?= e(base_url('orders.php')) ?>">← Vissza a rendeléslistához</a></p>
  <p><strong>Rendelésszám:</strong> #<?= (int)$order['order_id'] ?></p>
  <p><strong>Állapot:</strong> <?= e($order['status']) ?></p>
  <p><strong>Dátum:</strong> <?= e($order['placed_at']) ?></p>
  <p><strong>Fizetés módja:</strong> <?= e($order['payment_method'] ?? '—') ?></p>
</div>
<?php
$sidebarContent = ob_get_clean();
$sidebarTitle   = 'Rendelés';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar-filters.php';
?>

<header class="content__header">
  <h1 class="content__title">Rendelés #<?= (int)$order['order_id'] ?></h1>
  <p class="content__meta"><strong>Végösszeg:</strong> <?= number_format((float)$order['total_amount'], 2, '.', ' ') ?> Ft</p>
</header>

<section>
  <h2>Tételek</h2>
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
      <?php foreach ($order['items'] as $it): ?>
        <tr>
          <td>
            <strong><?= e($it['title'] ?? '—') ?></strong>
            <?php if (!empty($it['image_url'])): ?><br><img src="<?= e($it['image_url']) ?>" alt="" style="max-height:60px"><?php endif; ?>
          </td>
          <td><?= (int)$it['quantity'] ?></td>
          <td><?= number_format((float)$it['unit_price'], 2, '.', ' ') ?> Ft</td>
          <td><?= number_format((float)$it['unit_price'] * (int)$it['quantity'], 2, '.', ' ') ?> Ft</td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<section>
  <h2>Számlázási adatok</h2>
  <p>
    <?= e($order['bill_full_name'] ?? '—') ?><br>
    <?= e($order['bill_address1'] ?? '—') ?> <?= e($order['bill_address2'] ?? '') ?><br>
    <?= e($order['bill_postal_code'] ?? '—') ?> <?= e($order['bill_city'] ?? '—') ?><?= !empty($order['bill_country']) ? ', ' . e(strtoupper($order['bill_country'])) : '' ?>
  </p>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
