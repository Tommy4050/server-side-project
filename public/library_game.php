<?php
require __DIR__ . '/../src/bootstrap.php';
$me = Auth::user();
if (!$me) redirect(base_url('login.php'));

$gameId = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;
if ($gameId <= 0) redirect(base_url('library.php'));

$game = db()->prepare("SELECT * FROM games WHERE game_id=:g");
$game->execute([':g'=>$gameId]);
$game = $game->fetch();
if (!$game) redirect(base_url('library.php'));

$isAdmin = is_admin();
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$per  = 10;

$res = Post::feedWithTotal($me['user_id'], $gameId, null, $page, $per, $isAdmin);
$rows = $res['rows'];
$total= $res['total'];
$pages= (int)max(1, ceil($total / $per));

$title  = 'Könyvtár – ' . $game['title'];
$active = 'library';

include __DIR__ . '/partials/header.php';
?>
<main class="layout">
  <section class="content" aria-label="Tartalom">
    <header class="content__header">
      <h1 class="content__title"><?= e($game['title']) ?></h1>
      <p><a href="<?= e(base_url('library.php')) ?>">← Vissza a könyvtárhoz</a></p>
    </header>

    <h2>Közösségi képek ebből a játékból</h2>
    <p class="content__meta">Találatok: <strong><?= (int)$total ?></strong> · Oldal: <strong><?= (int)$page ?>/<?= (int)$pages ?></strong></p>

    <?php if (!$rows): ?>
      <p>Még nincs poszt ehhez a játékhoz.</p>
    <?php else: ?>
      <?php foreach ($rows as $p): ?>
        <article class="post" style="border:1px solid #ddd; padding:8px; margin-bottom:12px;">
          <header>
            <strong>
              <a href="<?= e(base_url('profile_public.php')) . '?user_id=' . (int)$p['user_id'] ?>">
                <?= e($p['username']) ?>
              </a>
            </strong>
            · <small><?= e($p['created_at']) ?></small>
            <?php if ($p['is_hidden']): ?><span> · <em>Rejtett</em></span><?php endif; ?>
          </header>
          <div style="margin:8px 0;">
            <img src="<?= e(asset_url($p['image_path'])) ?>" alt="" style="max-width:100%; height:auto">
          </div>
          <?php if (!empty($p['caption'])): ?>
            <p><?= nl2br(e($p['caption'])) ?></p>
          <?php endif; ?>

          <!-- (Optional) You can copy your like/comment/admin forms from feed.php here -->
          <p><a href="<?= e(base_url('feed.php')) . '?game_id=' . (int)$gameId ?>">Megnyitás a Közösség oldalon</a></p>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($total > 0): ?>
      <nav class="pagination" aria-label="Lapozás">
        <ul class="pagination__list">
          <li>
            <?php if ($page > 1): ?>
              <a href="?<?= e(http_build_query(array_merge($_GET, ['page'=>$page-1]))) ?>">&laquo; Előző</a>
            <?php else: ?><span>&laquo; Előző</span><?php endif; ?>
          </li>
          <li>
            <?php if ($page < $pages): ?>
              <a href="?<?= e(http_build_query(array_merge($_GET, ['page'=>$page+1]))) ?>">Következő &raquo;</a>
            <?php else: ?><span>Következő &raquo;</span><?php endif; ?>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
  </section>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
