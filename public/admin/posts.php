<?php
require __DIR__ . '/../../src/bootstrap.php';
require_admin();

$errors = [];
$notice = '';

$username = trim($_GET['username'] ?? '');
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$per  = 20;
$off  = ($page-1)*$per;

/** Actions: hide/unhide */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? null)) {
  $action = $_POST['action'] ?? '';
  $postId = (int)($_POST['post_id'] ?? 0);
  if ($postId > 0) {
    try {
      if ($action === 'hide') {
        $reason = trim($_POST['reason'] ?? '');
        db()->prepare("UPDATE posts SET is_hidden=1, hidden_reason=:r, moderated_by=:m WHERE post_id=:p")
          ->execute([':r'=>$reason !== '' ? $reason : null, ':m'=>(int)Auth::user()['user_id'], ':p'=>$postId]);
      } elseif ($action === 'unhide') {
        db()->prepare("UPDATE posts SET is_hidden=0, hidden_reason=NULL, moderated_by=NULL WHERE post_id=:p")
          ->execute([':p'=>$postId]);
      }
    } catch (Throwable $e) { $errors[] = $e->getMessage(); }
  }
  header('Location: ' . base_url('admin/posts.php') . ($username ? ('?username='.urlencode($username)) : ''));
  exit;
}

/** Count with optional username LIKE */
$where = [];
$params = [];
if ($username !== '') { $where[] = "u.username LIKE :uname"; $params[':uname'] = "%$username%"; }
$whereSql = $where ? ('WHERE '.implode(' AND ',$where)) : '';

$sqlC = "SELECT COUNT(*)
         FROM posts p
         JOIN users u ON u.user_id = p.user_id
         $whereSql";
$stC = db()->prepare($sqlC);
foreach ($params as $k=>$v) $stC->bindValue($k,$v,PDO::PARAM_STR);
$stC->execute();
$total = (int)$stC->fetchColumn();
$pages = (int)max(1, ceil($total/$per));

/** Page */
$sql = "SELECT p.post_id, p.user_id, p.game_id, p.caption, p.image_path, p.is_hidden, p.hidden_reason, p.created_at,
               u.username, g.title AS game_title
        FROM posts p
        JOIN users u ON u.user_id = p.user_id
        LEFT JOIN games g ON g.game_id = p.game_id
        $whereSql
        ORDER BY p.created_at DESC, p.post_id DESC
        LIMIT :lim OFFSET :off";
$st = db()->prepare($sql);
foreach ($params as $k=>$v) $st->bindValue($k,$v,PDO::PARAM_STR);
$st->bindValue(':lim',$per,PDO::PARAM_INT);
$st->bindValue(':off',$off,PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll();

$title='Admin – Posztok moderálása';
$active='admin';
include __DIR__ . '/../partials/header.php';
?>
<main class="layout">
  <section class="content" aria-label="Tartalom">
    <header class="content__header">
      <h1 class="content__title">Posztok moderálása</h1>
      <p><a href="<?= e(base_url('admin/index.php')) ?>">← Vissza a vezérlőpulthoz</a></p>
    </header>

    <form method="get" action="" style="margin-bottom:12px;">
      <label for="username">Keresés felhasználó szerint:</label>
      <input id="username" name="username" type="text" value="<?= e($username) ?>" placeholder="felhasználónév">
      <button type="submit">Keres</button>
      <a href="<?= e(base_url('admin/posts.php')) ?>">Töröl</a>
    </form>

    <p>Találatok: <strong><?= (int)$total ?></strong> · Oldal: <strong><?= (int)$page ?>/<?= (int)$pages ?></strong></p>

    <?php if (!$rows): ?>
      <p>Nincs találat.</p>
    <?php else: foreach ($rows as $p): ?>
      <article style="border:1px solid #ddd; padding:8px; margin-bottom:12px;">
        <header>
          <strong><?= e($p['username']) ?></strong>
          <?php if (!empty($p['game_title'])): ?> a(z) <em><?= e($p['game_title']) ?></em> játékból<?php endif; ?>
          · <small><?= e($p['created_at']) ?></small>
          <?php if ($p['is_hidden']): ?> · <em>Rejtett</em><?php if ($p['hidden_reason']) echo ' – '.e($p['hidden_reason']); endif; ?>
        </header>
        <?php if (!empty($p['image_path'])): ?>
          <div style="margin:8px 0;"><img src="<?= e(asset_url($p['image_path'])) ?>" alt="" style="max-width:100%;height:auto;"></div>
        <?php endif; ?>
        <?php if (!empty($p['caption'])): ?><p><?= nl2br(e($p['caption'])) ?></p><?php endif; ?>

        <form method="post" action="" style="margin-top:6px;">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="post_id" value="<?= (int)$p['post_id'] ?>">
          <?php if (!$p['is_hidden']): ?>
            <input type="hidden" name="action" value="hide">
            <input type="text" name="reason" placeholder="indok (opcionális)" style="width:200px;">
            <button type="submit">Elrejt</button>
          <?php else: ?>
            <input type="hidden" name="action" value="unhide">
            <button type="submit">Megjelenít</button>
          <?php endif; ?>
        </form>
      </article>
    <?php endforeach; endif; ?>

    <?php if ($total > 0): ?>
      <nav class="pagination" aria-label="Lapozás">
        <ul class="pagination__list">
          <li>
            <?php if ($page>1): ?>
              <a href="?<?= e(http_build_query(array_merge($_GET,['page'=>$page-1]))) ?>">&laquo; Előző</a>
            <?php else: ?><span>&laquo; Előző</span><?php endif; ?>
          </li>
          <li>
            <?php if ($page<$pages): ?>
              <a href="?<?= e(http_build_query(array_merge($_GET,['page'=>$page+1]))) ?>">Következő &raquo;</a>
            <?php else: ?><span>Következő &raquo;</span><?php endif; ?>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
  </section>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
