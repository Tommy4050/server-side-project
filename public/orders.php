<?php
require __DIR__ . '/../src/bootstrap.php';

$u = Auth::user();
if (!$u) redirect(base_url('login.php'));

$status = trim($_GET['status'] ?? '');
$from   = trim($_GET['from'] ?? '');
$to     = trim($_GET['to'] ?? '');
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per    = 10;

$res  = OrderModel::listForUser((int)$u['user_id'], $status ?: null, $from ?: null, $to ?: null, $page, $per);
$rows = $res['rows'];
$total= $res['total'];
$pages= (int)max(1, ceil($total / $per));

$title  = 'Rendeléseim';
$active = 'store';

ob_start(); ?>
<form method="get" action="">
  <div>
    <label for="status">Állapot</label><br>
    <select id="status" name="status">
      <option value="">-- mindegy --</option>
      <?php foreach (['paid'=>'Fizetve','pending'=>'Folyamatban','canceled'=>'Törölve','refunded'=>'Visszatérítve'] as $k=>$v): ?>
        <option value="<?= e($k) ?>"<?= $status===$k?' selected':'' ?>><?= e($v) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label for="from">Dátumtól (YYYY-MM-DD)</label><br>
    <input id="from" name="from" type="date" value="<?= e($from) ?>">
  </div>
  <div>
    <label for="to">Dátumig (YYYY-MM-DD)</label><br>
    <input id="to" name="to" type="date" value="<?= e($to) ?>">
  </div>
  <div>
    <button type="submit">Szűrés</button>
    <a href="<?= e(base_url('orders.php')) ?>">Szűrők törlése</a>
  </div>
</form>
<?php
$sidebarContent = ob_get_clean();
$sidebarTitle   = 'Szűrők';

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar-filters.php';
?>
<header class="content__header">
  <h1 class="content__title">Rendeléseim</h1>
  <p class="content__meta">Találatok: <strong><?= (int)$total ?></strong> · Oldal: <strong><?= (int)$page ?>/<?= (int)$pages ?></strong></p>
</header>

<?php if (!$rows): ?>
  <div class="empty">
    <p>Még nincs rendelésed.</p>
    <p><a href="<?= e(base_url('store.php')) ?>">Vissza az áruházhoz</a></p>
  </div>
<?php else: ?>
  <table border="1" cellpadding="6" cellspacing="0">
    <thead>
      <tr>
        <th>Rendelésszám</th>
        <th>Dátum</th>
        <th>Állapot</th>
        <th>Tételek</th>
        <th>Összeg</th>
        <th>Részletek</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>#<?= (int)$r['order_id'] ?></td>
          <td><?= e($r['placed_at']) ?></td>
          <td><?= e($r['status']) ?></td>
          <td><?= (int)$r['items'] ?></td>
          <td><?= number_format((float)$r['total_amount'], 2, '.', ' ') ?> Ft</td>
          <td><a href="<?= e(base_url('order.php')) . '?order_id=' . (int)$r['order_id'] ?>">Megnyitás</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <nav class="pagination" aria-label="Lapozás">
    <ul class="pagination__list">
      <li><?php if ($page>1): ?><a href="?<?= e(http_build_query(array_merge($_GET,['page'=>$page-1]))) ?>">&laquo; Előző</a><?php else: ?><span>&laquo; Előző</span><?php endif; ?></li>
      <li><?php if ($page<$pages): ?><a href="?<?= e(http_build_query(array_merge($_GET,['page'=>$page+1]))) ?>">Következő &raquo;</a><?php else: ?><span>Következő &raquo;</span><?php endif; ?></li>
    </ul>
  </nav>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
