<?php
require __DIR__ . '/../../src/bootstrap.php';
require_admin();

$errors = [];
$notice = '';

/* ---------- Actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? null)) {
  $uid    = (int)($_POST['user_id'] ?? 0);
  $action = $_POST['action'] ?? '';

  if ($uid > 0) {
    try {
      if ($action === 'make_admin') {
        db()->prepare("UPDATE users SET is_admin=1 WHERE user_id=:u")->execute([':u'=>$uid]);

      } elseif ($action === 'remove_admin') {
        db()->prepare("UPDATE users SET is_admin=0 WHERE user_id=:u")->execute([':u'=>$uid]);

      } elseif ($action === 'ban') {
        $amount = max(1, (int)($_POST['ban_amount'] ?? 1));
        $unit   = $_POST['ban_unit'] ?? 'days';
        $reason = trim($_POST['ban_reason'] ?? '');

        $interval = match ($unit) { 'minutes'=>"PT{$amount}M", 'hours'=>"PT{$amount}H", default=>"P{$amount}D" };
        $until = (new DateTime())->add(new DateInterval($interval))->format('Y-m-d H:i:s');

        db()->prepare("
          UPDATE users
             SET banned_until=:until, ban_reason=:reason, banned_by=:by
           WHERE user_id=:u
        ")->execute([
          ':until'=>$until,
          ':reason'=>($reason!=='' ? $reason : null),
          ':by'=>(int)Auth::user()['user_id'],
          ':u'=>$uid
        ]);

      } elseif ($action === 'unban') {
        db()->prepare("
          UPDATE users
             SET banned_until=NULL, ban_reason=NULL, banned_by=NULL
           WHERE user_id=:u
        ")->execute([':u'=>$uid]);
      }
    } catch (Throwable $e) { $errors[] = $e->getMessage(); }
  }

  header('Location: ' . base_url('admin/users.php') . (isset($_GET['q']) ? ('?q='.urlencode($_GET['q'])) : ''));
  exit;
}

/* ---------- Search + list ---------- */
$q    = trim($_GET['q'] ?? '');
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$per  = 25; $off = ($page-1)*$per;

$where = []; $params = [];
if ($q !== '') { $where[] = "(u.username LIKE :q OR u.email LIKE :q)"; $params[':q'] = "%$q%"; }
$whereSql = $where ? ('WHERE '.implode(' AND ',$where)) : '';

$stC = db()->prepare("SELECT COUNT(*) FROM users u $whereSql");
foreach ($params as $k=>$v) $stC->bindValue($k, $v, PDO::PARAM_STR);
$stC->execute();
$total = (int)$stC->fetchColumn();
$pages = (int)max(1, ceil($total/$per));

$sql = "SELECT u.user_id, u.username, u.email, u.is_admin, u.banned_until, u.ban_reason
        FROM users u
        $whereSql
        ORDER BY u.username
        LIMIT :lim OFFSET :off";
$st = db()->prepare($sql);
foreach ($params as $k=>$v) $st->bindValue($k, $v, PDO::PARAM_STR);
$st->bindValue(':lim',$per,PDO::PARAM_INT);
$st->bindValue(':off',$off,PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll();

$title='Admin – Felhasználók';
$active='admin';
include __DIR__ . '/../partials/header.php';
?>
<main class="layout">
  <section class="content" aria-label="Tartalom">
    <header class="content__header">
      <h1 class="content__title">Felhasználók</h1>
      <p><a href="<?= e(base_url('admin/index.php')) ?>">← Vissza a vezérlőpulthoz</a></p>
    </header>

    <?php if ($errors): ?>
      <div><h3>Hibák:</h3><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="get" action="" style="margin-bottom:12px;">
      <label for="q">Keresés (felhasználó / e-mail):</label>
      <input id="q" name="q" type="text" value="<?= e($q) ?>" placeholder="pl. test, user@example.com">
      <button type="submit">Keres</button>
      <a href="<?= e(base_url('admin/users.php')) ?>">Töröl</a>
    </form>

    <p>Találatok: <strong><?= (int)$total ?></strong> · Oldal: <strong><?= (int)$page ?>/<?= (int)$pages ?></strong></p>

    <table border="1" cellpadding="6" cellspacing="0">
      <thead><tr>
        <th>Felhasználó</th><th>Email</th><th>Admin</th><th>Állapot</th><th>Műveletek</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $u):
        $isBanned = false; $untilObj = null; $val = $u['banned_until'] ?? null;
        if ($val && $val !== '0000-00-00 00:00:00') { $untilObj = DateTime::createFromFormat('Y-m-d H:i:s', $val) ?: new DateTime($val); $isBanned = ($untilObj > new DateTime()); }
      ?>
        <tr>
          <td><?= e($u['username']) ?></td>
          <td><?= e($u['email']) ?></td>
          <td><?= (int)$u['is_admin'] ? 'Igen' : 'Nem' ?></td>
          <td>
            <?php if ($isBanned): ?>
              Tiltva eddig: <?= e($untilObj->format('Y-m-d H:i')) ?><?= !empty($u['ban_reason']) ? (' – Indok: '.e($u['ban_reason'])) : '' ?>
            <?php else: ?>Aktív<?php endif; ?>
          </td>
          <td>
            <!-- Admin toggle -->
            <form method="post" action="" style="display:inline">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
              <?php if (!(int)$u['is_admin']): ?>
                <input type="hidden" name="action" value="make_admin">
                <button type="submit">Adminná tesz</button>
              <?php else: ?>
                <input type="hidden" name="action" value="remove_admin">
                <button type="submit">Admin jog elvétele</button>
              <?php endif; ?>
            </form>

            <!-- Ban / Unban -->
            <?php if (!$isBanned): ?>
              <form method="post" action="" style="display:inline; margin-left:6px;">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                <input type="hidden" name="action" value="ban">
                <label>Időtartam:
                  <input type="number" name="ban_amount" value="7" min="1" style="width:60px;">
                  <select name="ban_unit">
                    <option value="minutes">perc</option>
                    <option value="hours">óra</option>
                    <option value="days" selected>nap</option>
                  </select>
                </label>
                <label>Indok:
                  <input type="text" name="ban_reason" placeholder="(opcionális)" style="width:160px;">
                </label>
                <button type="submit">Tiltás</button>
              </form>
            <?php else: ?>
              <form method="post" action="" style="display:inline; margin-left:6px;">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                <input type="hidden" name="action" value="unban">
                <button type="submit">Tiltás feloldása</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($total > 0): ?>
      <nav class="pagination" aria-label="Lapozás" style="margin-top:10px;">
        <ul class="pagination__list">
          <li><?php if ($page>1): ?><a href="?<?= e(http_build_query(array_merge($_GET,['page'=>$page-1]))) ?>">&laquo; Előző</a><?php else: ?><span>&laquo; Előző</span><?php endif; ?></li>
          <li><?php if ($page<$pages): ?><a href="?<?= e(http_build_query(array_merge($_GET,['page'=>$page+1]))) ?>">Következő &raquo;</a><?php else: ?><span>Következő &raquo;</span><?php endif; ?></li>
        </ul>
      </nav>
    <?php endif; ?>
  </section>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
