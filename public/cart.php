<?php
require __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Cart.php';

$me = Auth::user();
if (!$me) redirect(base_url('login.php'));

$data = Cart::getActiveCart((int)$me['user_id']);
$items = $data['items'];
$subtotal = $data['subtotal'];

$title = 'Kosár';
$active = 'store';
include __DIR__ . '/partials/header.php';
?>
<header class="content__header">
  <h1 class="content__title">Kosár</h1>
</header>

<?php if (!$items): ?>
  <p>A kosár üres.</p>
  <p><a href="<?= e(base_url('store.php')) ?>">Vissza az áruházba</a></p>
<?php else: ?>
  <table border="1" cellpadding="8" cellspacing="0">
    <thead>
      <tr>
        <th>Termék</th>
        <th>Egységár</th>
        <th>Mennyiség</th>
        <th>Sorösszeg</th>
        <th>Műveletek</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td>
            <?= e($it['title']) ?><br>
            <?php if (!empty($it['image_url'])): ?>
              <img src="<?= e($it['image_url']) ?>" alt="" style="max-height:60px;">
            <?php endif; ?>
          </td>
          <td><?= number_format((float)$it['unit_price'], 2, '.', ' ') ?> Ft</td>
          <td>
            <form method="post" action="<?= e(base_url('cart_update.php')) ?>" style="display:inline-flex; gap:6px;">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="cart_item_id" value="<?= (int)$it['cart_item_id'] ?>">
              <input type="number" name="quantity" min="1" max="99" value="<?= (int)$it['quantity'] ?>">
              <button type="submit">Módosít</button>
            </form>
          </td>
          <td><?= number_format((float)$it['line_total'], 2, '.', ' ') ?> Ft</td>
          <td>
            <form method="post" action="<?= e(base_url('cart_remove.php')) ?>" onsubmit="return confirm('Biztosan törlöd?');">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="cart_item_id" value="<?= (int)$it['cart_item_id'] ?>">
              <button type="submit">Eltávolítás</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <th colspan="3" style="text-align:right;">Részösszeg:</th>
        <th><?= number_format((float)$subtotal, 2, '.', ' ') ?> Ft</th>
        <th></th>
      </tr>
    </tfoot>
  </table>

  <p style="margin-top:12px;">
    <a href="<?= e(base_url('checkout.php')) ?>">Tovább a pénztárhoz</a>
  </p>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
