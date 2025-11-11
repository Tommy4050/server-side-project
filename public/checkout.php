<?php
require __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Cart.php';

$me = Auth::user();
if (!$me) redirect(base_url('login.php'));

// ------- helpers for adaptive schema -------
function table_has_column(string $table, string $column): bool {
    $st = db()->prepare("SHOW COLUMNS FROM `$table` LIKE :c");
    $st->execute([':c' => $column]);
    return (bool)$st->fetchColumn();
}
function first_existing_col(string $table, array $candidates): ?string {
    foreach ($candidates as $c) if (table_has_column($table, $c)) return $c;
    return null;
}

// ------- handle submit (POST) -------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf($_POST['csrf'] ?? null)) {
            throw new RuntimeException('Érvénytelen űrlap token.');
        }

        // Load active cart
        $cart  = Cart::getActiveCart((int)$me['user_id']);
        $items = $cart['items'] ?? [];
        if (!$items) throw new RuntimeException('A kosár üres.');

        // Compute total from captured unit prices
        $total = 0.0;
        foreach ($items as $it) $total += ((float)$it['unit_price']) * (int)$it['quantity'];

        db()->beginTransaction();

        // Insert into orders (adaptive columns)
        $orderCols = ['user_id' => (int)$me['user_id']];
        $totalCol  = first_existing_col('orders', ['total_amount','total','amount']);
        if ($totalCol) $orderCols[$totalCol] = $total;
        $statusCol = first_existing_col('orders', ['status','order_status']);
        if ($statusCol) $orderCols[$statusCol] = 'paid';
        $tsCol     = first_existing_col('orders', ['created_at','order_date','placed_at','created_on']);

        $cols = array_keys($orderCols);
        if ($tsCol) $cols[] = $tsCol;

        $ph = [];
        foreach ($orderCols as $k => $_) $ph[] = ':' . $k;
        if ($tsCol) $ph[] = 'NOW()';

        $sqlOrder = "INSERT INTO orders (" . implode(',', $cols) . ") VALUES (" . implode(',', $ph) . ")";
        $stOrder  = db()->prepare($sqlOrder);
        foreach ($orderCols as $k => $v) $stOrder->bindValue(':' . $k, $v);
        $stOrder->execute();
        $orderId = (int)db()->lastInsertId();

        // Insert order_items (adaptive)
        $qtyCol   = first_existing_col('order_items', ['quantity','qty','count']);
        $priceCol = first_existing_col('order_items', ['unit_price','price','price_at_purchase','unit_price_at_add']);
        if (!$qtyCol || !$priceCol) throw new RuntimeException('Az order_items táblában hiányzik a mennyiség vagy ár oszlop.');
        if (!table_has_column('order_items','order_id')) throw new RuntimeException('Hiányzik: order_items.order_id');
        if (!table_has_column('order_items','game_id'))  throw new RuntimeException('Hiányzik: order_items.game_id');

        $sqlItem = "INSERT INTO order_items (order_id, game_id, $qtyCol, $priceCol) VALUES (:o,:g,:q,:p)";
        $stItem  = db()->prepare($sqlItem);
        foreach ($items as $it) {
            $stItem->execute([
                ':o' => $orderId,
                ':g' => (int)$it['game_id'],
                ':q' => (int)$it['quantity'],
                ':p' => (float)$it['unit_price'],
            ]);
        }

        // Upsert into libraries
        $libCols = ['user_id','game_id'];
        $libVals = [':u',':g'];
        $params  = [':u' => (int)$me['user_id']];

        $acqCol = first_existing_col('libraries', ['acquired_at','added_at']);
        if ($acqCol) { $libCols[] = $acqCol; $libVals[] = 'NOW()'; }
        if (table_has_column('libraries','source')) { $libCols[] = 'source'; $libVals[] = ':src'; $params[':src'] = 'purchase'; }
        if (table_has_column('libraries','total_play_seconds')) { $libCols[] = 'total_play_seconds'; $libVals[] = '0'; }

        $sqlLib = "INSERT INTO libraries (" . implode(',', $libCols) . ")
                   VALUES (" . implode(',', $libVals) . ")
                   ON DUPLICATE KEY UPDATE " . (table_has_column('libraries','source') ? "source=VALUES(source)" : "game_id=game_id");
        $stLib = db()->prepare($sqlLib);
        foreach ($items as $it) {
            $params[':g'] = (int)$it['game_id'];
            $stLib->execute($params);
        }

        // Mark cart converted
        $cartId = (int)($cart['cart']['cart_id'] ?? 0);
        if ($cartId > 0) {
            $set = "status='converted'";
            if (table_has_column('shopping_carts','updated_at')) $set .= ", updated_at=NOW()";
            db()->prepare("UPDATE shopping_carts SET $set WHERE cart_id=:id")->execute([':id'=>$cartId]);

            // Optionally clear items:
            // db()->prepare("DELETE FROM cart_items WHERE cart_id=:id")->execute([':id'=>$cartId]);
        }

        db()->commit();

        // Go to success page
        redirect(base_url('checkout_success.php') . '?order_id=' . $orderId);

    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        $msg = urlencode($e->getMessage());
        redirect(base_url('checkout.php') . "?error={$msg}");
    }
}

// ------- render page (GET) -------
$data     = Cart::getActiveCart((int)$me['user_id']);
$items    = $data['items'];
$subtotal = $data['subtotal'];

$title  = 'Pénztár';
$active = 'store';
include __DIR__ . '/partials/header.php';
?>
<header class="content__header">
  <h1 class="content__title">Pénztár</h1>
</header>
<?php if (!empty($_GET['error'])): ?>
  <p style="color:#a00;"><?= e($_GET['error']) ?></p>
<?php endif; ?>

<?php if (!$items): ?>
  <p>A kosár üres.</p>
  <p><a href="<?= e(base_url('store.php')) ?>">Vissza az áruházba</a></p>
<?php else: ?>

  <h2>Rendelési összegzés</h2>
  <table border="1" cellpadding="8" cellspacing="0">
    <thead>
      <tr>
        <th>Termék</th>
        <th>Egységár</th>
        <th>Mennyiség</th>
        <th>Sorösszeg</th>
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
          <td><?= (int)$it['quantity'] ?></td>
          <td><?= number_format((float)$it['line_total'], 2, '.', ' ') ?> Ft</td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <th colspan="3" style="text-align:right;">Részösszeg:</th>
        <th><?= number_format((float)$subtotal, 2, '.', ' ') ?> Ft</th>
      </tr>
    </tfoot>
  </table>

  <form method="post" action="" style="margin-top:12px;">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <button type="submit">Megrendelés leadása</button>
    &nbsp; <a href="<?= e(base_url('cart.php')) ?>">Vissza a kosárhoz</a>
  </form>

<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
