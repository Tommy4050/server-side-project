<?php
require __DIR__ . '/../src/bootstrap.php';

$gameId = (int)($_GET['game_id'] ?? 0);
if ($gameId <= 0) redirect(base_url('store.php'));

// fetch game
$st = db()->prepare("
  SELECT game_id, title, description, publisher, release_date, price, image_url,
         sale_percent, sale_start, sale_end, is_published
  FROM games
  WHERE game_id = :id
  LIMIT 1
");
$st->execute([':id'=>$gameId]);
$game = $st->fetch();
if (!$game || (int)$game['is_published'] !== 1) {
  redirect(base_url('store.php'));
}

$activeSale = ((int)$game['sale_percent'] > 0)
  && (empty($game['sale_start']) || $game['sale_start'] <= date('Y-m-d'))
  && (empty($game['sale_end'])   || $game['sale_end']   >= date('Y-m-d'));

$finalPrice = $game['price'];
if ($activeSale && $game['price'] !== null) {
  $finalPrice = round(((float)$game['price']) * (100 - (int)$game['sale_percent']) / 100, 2);
}

// genres (optional nice-to-have on detail)
$gx = db()->prepare("
  SELECT ge.name
  FROM game_genres gg
  JOIN genres ge ON ge.genre_id = gg.genre_id
  WHERE gg.game_id = :id
  ORDER BY ge.name
");
$gx->execute([':id'=>$gameId]);
$genres = array_column($gx->fetchAll(), 'name');

$title  = e($game['title']);
$active = 'store';

include __DIR__ . '/partials/header.php';
?>

<header class="content__header">
  <h1 class="content__title"><?= e($game['title']) ?></h1>
</header>

<article class="game-detail">
  <div class="game-detail__media">
    <?php if (!empty($game['image_url'])): ?>
      <img src="<?= e($game['image_url']) ?>" alt="<?= e($game['title']) ?>" style="max-width:100%;height:auto;">
    <?php endif; ?>
  </div>
  <div class="game-detail__body">
    <?php if (!empty($genres)): ?>
      <p><strong>Műfajok:</strong> <?= e(implode(', ', $genres)) ?></p>
    <?php endif; ?>

    <?php if (!empty($game['publisher'])): ?>
      <p><strong>Kiadó:</strong> <?= e($game['publisher']) ?></p>
    <?php endif; ?>

    <?php if (!empty($game['release_date'])): ?>
      <p><strong>Megjelenés:</strong> <?= e($game['release_date']) ?></p>
    <?php endif; ?>

    <?php if (!empty($game['description'])): ?>
      <p><?= nl2br(e($game['description'])) ?></p>
    <?php endif; ?>

    <div class="game-detail__buy">
      <?php if ($activeSale): ?>
        <div>
          <span class="badge">-<?= (int)$game['sale_percent'] ?>%</span>
          <span style="text-decoration:line-through; opacity:.7; margin-left:6px;">
            <?= number_format((float)$game['price'], 2, '.', ' ') ?> Ft
          </span>
          <span style="font-weight:700; margin-left:6px;">
            <?= number_format((float)$finalPrice, 2, '.', ' ') ?> Ft
          </span>
        </div>
      <?php else: ?>
        <div><strong><?= number_format((float)$game['price'], 2, '.', ' ') ?> Ft</strong></div>
      <?php endif; ?>

      <!-- Add to cart: returns here and auto-opens mini-cart -->
      <form method="post" action="<?= e(base_url('add_to_cart.php')) ?>" style="margin-top:8px;">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="game_id" value="<?= (int)$game['game_id'] ?>">
        <input type="hidden" name="back_url" value="<?= e($_SERVER['REQUEST_URI']) ?>">
        <button type="submit">Kosárba</button>
      </form>
    </div>
  </div>
</article>

<?php include __DIR__ . '/partials/footer.php'; ?>
