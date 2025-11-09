<?php
require __DIR__ . '/../src/bootstrap.php';

$viewer = Auth::user();
$isAdmin = $viewer ? (bool)(db()->prepare("SELECT is_admin FROM users WHERE user_id = :u")->execute([':u'=>(int)$viewer['user_id']]) ?: false) : false;
// Fetch admin flag properly:
if ($viewer) {
  $stAdm = db()->prepare("SELECT is_admin FROM users WHERE user_id = :u LIMIT 1");
  $stAdm->execute([':u'=>(int)$viewer['user_id']]);
  $isAdmin = (bool)$stAdm->fetchColumn();
}

$userIdParam = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$usernameParam = trim($_GET['username'] ?? '');

if ($userIdParam) {
    $st = db()->prepare("SELECT user_id, username FROM users WHERE user_id = :id LIMIT 1");
    $st->execute([':id' => $userIdParam]);
} elseif ($usernameParam !== '') {
    $st = db()->prepare("SELECT user_id, username FROM users WHERE username = :un LIMIT 1");
    $st->execute([':un' => $usernameParam]);
} else {
    http_response_code(400);
    echo "<!doctype html><meta charset='utf-8'><h1>Hiányzó paraméter</h1><p>Adj meg user_id vagy username paramétert.</p>";
    exit;
}
$author = $st->fetch();
$viewer = Auth::user();
$viewerId = $viewer ? (int)$viewer['user_id'] : null;

$relationship = null; // 'self', 'friends', 'pending_sent', 'pending_recv', 'none'
if ($viewerId && $viewerId === (int)$author['user_id']) {
  $relationship = 'self';
} elseif ($viewerId && Friend::areFriends($viewerId, (int)$author['user_id'])) {
  $relationship = 'friends';
} elseif ($viewerId && ($p = Friend::pendingBetween($viewerId, (int)$author['user_id']))) {
  $relationship = ($p['requester_id'] == $viewerId) ? 'pending_sent' : 'pending_recv';
} else {
  $relationship = 'none';
}

if (!$author) {
    http_response_code(404);
    echo "<!doctype html><meta charset='utf-8'><h1>404 – Felhasználó nem található</h1>";
    exit;
}

$gameFilter = ($_GET['game_id'] ?? '') !== '' ? (int)$_GET['game_id'] : null;
$page       = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$per        = 10;

$res   = Post::feedWithTotal($viewer['user_id'] ?? null, $gameFilter, (int)$author['user_id'], $page, $per, $isAdmin);
$rows  = $res['rows'];
$total = $res['total'];
$pages = (int)max(1, ceil($total / $per));

// games for filter
$games = db()->query("SELECT game_id, title FROM games WHERE is_published=1 ORDER BY title")->fetchAll();

$title  = 'Felhasználói posztok';
$active = 'community';

// Build sidebar (filter + link back)
ob_start(); ?>
<form method="get" action="">
  <input type="hidden" name="user_id" value="<?= (int)$author['user_id'] ?>">
  <div>
    <label for="game_id">Szűrés játék szerint</label><br>
    <select id="game_id" name="game_id">
      <option value="">-- összes --</option>
      <?php foreach ($games as $g): ?>
        <option value="<?= (int)$g['game_id'] ?>"<?= ($gameFilter===(int)$g['game_id']?' selected':'') ?>>
          <?= e($g['title']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <button type="submit">Szűrés</button>
    <a href="<?= e(base_url('profile_public.php')) . '?user_id=' . (int)$author['user_id'] ?>">Szűrő törlése</a>
  </div>
  <p><a href="<?= e(base_url('feed.php')) ?>">← Vissza a közösségi feedhez</a></p>
</form>
<?php
$sidebarContent = ob_get_clean();
$sidebarTitle   = 'Szűrők';

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar-filters.php';
?>

<header class="content__header">
  <h1 class="content__title"><?= e($author['username']) ?> – nyilvános posztok</h1>
  <p class="content__meta">Találatok: <strong><?= (int)$total ?></strong> · Oldal: <strong><?= (int)$page ?>/<?= (int)$pages ?></strong></p>
</header>

<?php if (!$rows): ?>
  <p>Nincs megjeleníthető poszt.</p>
<?php else: ?>
  <?php foreach ($rows as $p): ?>
    <article class="post" style="border:1px solid #ddd; padding:8px; margin-bottom:12px;">
      <header>
        <strong><?= e($p['username']) ?></strong>
        <?php if (!empty($p['game_title'])): ?> a(z) <em><?= e($p['game_title']) ?></em> játékból<?php endif; ?>
        · <small><?= e($p['created_at']) ?></small>
        <?php if ($p['is_hidden']): ?><span> · <em>Rejtett</em></span><?php endif; ?>
      </header>

      <div class="post__media" style="margin:8px 0;">
        <img src="<?= e(asset_url($p['image_path'])) ?>" alt="" style="max-width:100%; height:auto">
      </div>

      <?php if (!empty($p['caption'])): ?>
        <p><?= nl2br(e($p['caption'])) ?></p>
      <?php endif; ?>

      <p><small>Tetszik: <?= (int)$p['likes_count'] ?> · Hozzászólások: <?= (int)$p['comments_count'] ?></small></p>

      <p>
        <a href="<?= e(base_url('feed.php')) ?>">Vissza a feedhez</a>
      </p>
    </article>
  <?php endforeach; ?>

  <?php if ($viewerId && $relationship !== 'self'): ?>
    <div style="margin:8px 0;">
      <?php if ($relationship === 'friends'): ?>
        <form method="post" action="<?= e(base_url('friends_actions.php')) ?>" style="display:inline">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="unfriend">
          <input type="hidden" name="other_user_id" value="<?= (int)$author['user_id'] ?>">
          <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>">
          <button type="submit">Barát törlése</button>
        </form>

      <?php elseif ($relationship === 'pending_sent'): ?>
        <form method="post" action="<?= e(base_url('friends_actions.php')) ?>" style="display:inline">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="cancel">
          <input type="hidden" name="other_user_id" value="<?= (int)$author['user_id'] ?>">
          <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>">
          <button type="submit">Felkérés visszavonása</button>
        </form>

      <?php elseif ($relationship === 'pending_recv'): ?>
        <form method="post" action="<?= e(base_url('friends_actions.php')) ?>" style="display:inline">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="accept">
          <input type="hidden" name="other_user_id" value="<?= (int)$author['user_id'] ?>">
          <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>">
          <button type="submit">Elfogadás</button>
        </form>
        <form method="post" action="<?= e(base_url('friends_actions.php')) ?>" style="display:inline">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="decline">
          <input type="hidden" name="other_user_id" value="<?= (int)$author['user_id'] ?>">
          <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>">
          <button type="submit">Elutasítás</button>
        </form>

      <?php else: /* none */ ?>
        <form method="post" action="<?= e(base_url('friends_actions.php')) ?>" style="display:inline">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="send">
          <input type="hidden" name="other_user_id" value="<?= (int)$author['user_id'] ?>">
          <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>">
          <button type="submit">Barátnak jelölés</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>


  <nav class="pagination" aria-label="Lapozás">
    <ul class="pagination__list">
      <li>
        <?php if ($page > 1): ?>
          <a href="?<?= e(http_build_query(array_merge($_GET, ['page' => $page - 1, 'user_id' => (int)$author['user_id']]))) ?>">&laquo; Előző</a>
        <?php else: ?>
          <span>&laquo; Előző</span>
        <?php endif; ?>
      </li>
      <li>
        <?php if ($page < $pages): ?>
          <a href="?<?= e(http_build_query(array_merge($_GET, ['page' => $page + 1, 'user_id' => (int)$author['user_id']]))) ?>">Következő &raquo;</a>
        <?php else: ?>
          <span>Következő &raquo;</span>
        <?php endif; ?>
      </li>
    </ul>
  </nav>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
