<?php
require __DIR__ . '/../src/bootstrap.php';

$u = Auth::user();
if (!$u) redirect(base_url('login.php'));

$errors = [];
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $errors[] = 'Érvénytelen űrlap token.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'update' || $action === 'remove') {
                $cartItemId = (int)($_POST['cart_item_id'] ?? 0);
                if ($cartItemId <= 0) throw new RuntimeException('Érvénytelen tétel.');
            }

            if ($action === 'update') {
                $qty = (int)($_POST['quantity'] ?? 1);
                Cart::updateItemQuantity((int)$u['user_id'], $cartItemId, $qty);
                $notice = 'Tétel frissítve.';
            } elseif ($action === 'remove') {
                Cart::removeItem((int)$u['user_id'], $cartItemId);
                $notice = 'Tétel eltávolítva.';
            } elseif ($action === 'checkout') {
                // redirect to checkout page
                header('Location: ' . base_url('checkout.php'));
                exit;
            }
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$data = Cart::getActiveCart((int)$u['user_id']);
$rows = $data['rows'];
$total = (float)$data['total'];

$title = 'Kosár';
$active = 'store';
include __DIR__ . '/partials/header.php';

$sidebarTitle = 'Kosár összegzés';
ob_start();
?>
<div>
  <p><strong>Tételek:</strong> <?= (int)Cart::itemCount((int)$u['user_id']) ?></p>
  <p><strong>Végösszeg:</strong> <?= number_format($total, 2, '.', ' ') ?> Ft</p>
</div>
<form method="post" action="">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="action" value="checkout">
  <button type="submit">Tovább a fizetéshez</button>
</form>
<?php
$sidebarContent = ob_get_clean();
include __DIR__ . '/partials/sidebar-filters.php';
?>

<header class="content__header">
  <h1 class="content__title">Kosár</h1>
</header>

<?php if ($notice): ?><div><strong><?= e($notice) ?></strong></div><?php endif; ?>
<?php if ($errors): ?>
  <div>
    <h3>Hibák:</h3>
    <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<?php if (!$rows): ?>
  <div class="empty empty--cart">
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
        <th>Műveletek</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>
            <strong><?= e($r['title'] ?? '—') ?></strong>
            <?php if (!empty($r['image_url'])): ?><br><img src="<?= e($r['image_url']) ?>" alt="" style="max-height:60px"><?php endif; ?>
          </td>
          <td>
            <form method="post" action="" style="display:inline">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="cart_item_id" value="<?= (int)$r['cart_item_id'] ?>">
              <input type="number" name="quantity" value="<?= (int)$r['quantity'] ?>" min="1" max="99" style="width:60px">
              <button type="submit">Mentés</button>
            </form>
          </td>
          <td><?= number_format((float)$r['unit_price_at_add'], 2, '.', ' ') ?> Ft</td>
          <td><?= number_format((float)$r['line_total'], 2, '.', ' ') ?> Ft</td>
          <td>
            <form method="post" action="" style="display:inline">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="remove">
              <input type="hidden" name="cart_item_id" value="<?= (int)$r['cart_item_id'] ?>">
              <button type="submit">Eltávolítás</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="3" align="right"><strong>Összesen:</strong></td>
        <td><strong><?= number_format($total, 2, '.', ' ') ?> Ft</strong></td>
        <td></td>
      </tr>
    </tfoot>
  </table>

  <p>
    <form method="post" action="">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="checkout">
      <button type="submit">Tovább a fizetéshez</button>
    </form>
  </p>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
