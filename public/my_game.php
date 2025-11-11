<?php
require __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Post.php';

$me = Auth::user();
if (!$me) redirect(base_url('login.php'));

$gameId = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;
if ($gameId <= 0) redirect(base_url('library.php'));

// Load game (tweak column names if your schema differs)
$st = db()->prepare("
  SELECT g.game_id, g.title, g.description, g.image_url, g.publisher, g.release_date
  FROM games g
  WHERE g.game_id = :g
  LIMIT 1
");
$st->execute([':g' => $gameId]);
$game = $st->fetch();
if (!$game) redirect(base_url('library.php'));

// Genres (optional; ignore if you don’t have these tables)
$genres = [];
try {
  $gx = db()->prepare("
    SELECT ge.name
    FROM game_genres gg
    JOIN genres ge ON ge.genre_id = gg.genre_id
    WHERE gg.game_id = :g
    ORDER BY ge.name
  ");
  $gx->execute([':g'=>$gameId]);
  $genres = array_column($gx->fetchAll(), 'name');
} catch (Throwable $e) {
  // ignore if not present
}

// Pagination for user's own posts for this game
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$per  = 10;

// Only the current user's posts, for this game, hidden excluded
$isAdmin = false; // you viewing your own page; hidden posts shouldn't show
$res   = Post::feedWithTotal((int)$me['user_id'], $gameId, (int)$me['user_id'], $page, $per, $isAdmin, null);
$rows  = $res['rows'];
$total = $res['total'];
$pages = (int)max(1, ceil($total / $per));

$title  = 'Játékom – ' . $game['title'];
$active = 'library';

include __DIR__ . '/partials/header.php';
?>
<main class="layout">
  <section class="content" aria-label="Tartalom">
    <header class="content__header">
      <h1 class="content__title"><?= e($game['title']) ?></h1>
      <p>
        <a href="<?= e(base_url('library.php')) ?>">← Vissza a könyvtárhoz</a>
        &nbsp;·&nbsp;
        <a href="<?= e(base_url('feed.php')) . '?game_id=' . (int)$game['game_id'] ?>">Közösségi posztok</a>
        &nbsp;·&nbsp;
        <a href="<?= e(base_url('upload.php')) ?>">Új kép feltöltése</a>
      </p>
    </header>

    <article class="game-detail" style="display:grid; grid-template-columns: 260px 1fr; gap:16px; align-items:start;">
      <aside>
        <?php if (!empty($game['image_url'])): ?>
          <img src="<?= e($game['image_url']) ?>" alt="<?= e($game['title']) ?>" style="max-width:100%; height:auto;">
        <?php else: ?>
          <div style="width:100%; height:150px; border:1px solid #ddd; display:flex; align-items:center; justify-content:center;">No image</div>
        <?php endif; ?>
      </aside>

      <div>
        <?php if (!empty($genres)): ?>
          <p><strong>Műfajok:</strong> <?= e(implode(', ', $genres)) ?></p>
        <?php endif; ?>
        <?php if (!empty($game['publisher'])): ?>
          <p><strong>Kiadó:</strong> <?= e($game['publisher']) ?></p>
        <?php endif; ?>
        <?php
            // Show year (and optionally full date) from release_date
            if (!empty($game['release_date']) && $game['release_date'] !== '0000-00-00') {
                // Try to parse as Y-m-d, fall back to generic DateTime
                $dt = DateTime::createFromFormat('Y-m-d', $game['release_date']) ?: new DateTime($game['release_date']);
                ?>
                <p><strong>Megjelenés éve:</strong> <?= e($dt->format('Y')) ?></p>
                <!-- If you also want the full date, uncomment the next line -->
                <!-- <p><strong>Megjelenés dátuma:</strong> <?= e($dt->format('Y-m-d')) ?></p> -->
                <?php
            }
        ?>


        <?php if (!empty($game['description'])): ?>
          <h3>Leírás</h3>
          <p><?= nl2br(e($game['description'])) ?></p>
        <?php endif; ?>
      </div>
    </article>

    <hr style="margin:16px 0;">

    <h2>Saját posztjaim ebből a játékból</h2>
    <p class="content__meta">Találatok: <strong><?= (int)$total ?></strong> · Oldal: <strong><?= (int)$page ?>/<?= (int)$pages ?></strong></p>

    <?php if (!$rows): ?>
      <p>Még nem töltöttél fel képet ehhez a játékhoz.</p>
    <?php else: ?>
      <?php foreach ($rows as $p): ?>
        <article class="post" style="border:1px solid #ddd; padding:8px; margin-bottom:12px;">
          <header>
            <strong><?= e($p['username']) ?></strong>
            · <small><?= e($p['created_at']) ?></small>
          </header>
          <div style="margin:8px 0;">
            <img src="<?= e(asset_url($p['image_path'])) ?>" alt="" style="max-width:100%; height:auto">
          </div>
          <?php if (!empty($p['caption'])): ?>
            <p><?= nl2br(e($p['caption'])) ?></p>
          <?php endif; ?>
          <p><small>Tetszik: <?= (int)$p['likes_count'] ?> · Hozzászólások: <?= (int)$p['comments_count'] ?></small></p>
          <p><a href="<?= e(base_url('feed.php')) . '?game_id=' . (int)$gameId ?>">Megnyitás a Közösség oldalon</a></p>
        </article>
      <?php endforeach; ?>

      <?php if ($total > 0): ?>
        <nav class="pagination" aria-label="Lapozás">
          <ul class="pagination__list">
            <li>
              <?php if ($page > 1): ?>
                <a href="?<?= e(http_build_query(['game_id'=>$gameId,'page'=>$page-1])) ?>">&laquo; Előző</a>
              <?php else: ?><span>&laquo; Előző</span><?php endif; ?>
            </li>
            <li>
              <?php if ($page < $pages): ?>
                <a href="?<?= e(http_build_query(['game_id'=>$gameId,'page'=>$page+1])) ?>">Következő &raquo;</a>
              <?php else: ?><span>Következő &raquo;</span><?php endif; ?>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </section>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
