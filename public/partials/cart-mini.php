<?php
/** Mini cart popup. Works whether caller set $me or $u (from header). */
$me = $me ?? ($u ?? null);
$mini = null;

if ($me) {
  require_once __DIR__ . '/../../src/Cart.php';
  try {
    $mini = Cart::mini((int)$me['user_id']);
  } catch (Throwable $e) {
    $mini = ['items'=>[], 'total'=>0, 'count'=>0];
  }
}
?>
<div id="miniCart" class="mini-cart" aria-hidden="true">
  <div class="mini-cart__head">
    <strong>Kosár</strong>
    <button type="button" class="mini-cart__close" aria-label="Bezárás">&times;</button>
  </div>

  <?php if (!$me): ?>
    <div class="mini-cart__body">
      <p><a href="<?= e(base_url('login.php')) ?>">Jelentkezz be</a> a kosár megtekintéséhez.</p>
    </div>
  <?php elseif (empty($mini['items'])): ?>
    <div class="mini-cart__body">
      <p>A kosár üres.</p>
    </div>
  <?php else: ?>
    <div class="mini-cart__body">
      <ul class="mini-cart__list">
        <?php foreach ($mini['items'] as $it): ?>
          <li class="mini-cart__item">
            <div class="mini-cart__thumb">
              <?php if (!empty($it['image_url'])): ?>
                <img src="<?= e($it['image_url']) ?>" alt="">
              <?php else: ?>
                <div class="mini-cart__placeholder">No image</div>
              <?php endif; ?>
            </div>

            <div class="mini-cart__info">
              <div class="mini-cart__title"><?= e($it['title']) ?></div>

              <div class="mini-cart__meta">
                <form method="post" action="<?= e(base_url('cart_update.php')) ?>" class="mini-cart__form">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="cart_item_id" value="<?= (int)$it['cart_item_id'] ?>">
                  <input type="number" name="quantity" min="1" max="99" value="<?= (int)$it['quantity'] ?>">
                  <button type="submit">Módosít</button>
                </form>

                <form method="post" action="<?= e(base_url('cart_remove.php')) ?>" class="mini-cart__form">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="cart_item_id" value="<?= (int)$it['cart_item_id'] ?>">
                  <button type="submit">Töröl</button>
                </form>
              </div>
            </div>

            <div class="mini-cart__price">
              <?= number_format((float)$it['unit_price'] * (int)$it['quantity'], 2, '.', ' ') ?> Ft
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="mini-cart__foot">
      <div class="mini-cart__total">
        Összesen: <strong><?= number_format((float)$mini['total'], 2, '.', ' ') ?> Ft</strong>
      </div>
      <div class="mini-cart__actions">
        <a href="<?= e(base_url('checkout.php')) ?>">Pénztár</a>
      </div>
    </div>
  <?php endif; ?>
</div>
