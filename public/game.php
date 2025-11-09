<?php
require __DIR__ . '/../src/bootstrap.php';

$gameId = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;

// Fetch game + genres
$st = db()->prepare("
  SELECT g.game_id, g.title, g.description, g.price, g.image_url, g.publisher, g.release_date
  FROM games g
  WHERE g.game_id = :id AND g.is_published = 1
  LIMIT 1
");
$st->execute([':id' => $gameId]);
$game = $st->fetch();

if (!$game) {
    http_response_code(404);
    echo "<!doctype html><meta charset='utf-8'><h1>404 – A játék nem található</h1>";
    exit;
}

$st2 = db()->prepare("
  SELECT ge.name
  FROM game_genres gg
  JOIN genres ge ON ge.genre_id = gg.genre_id
  WHERE gg.game_id = :id
  ORDER BY ge.name
");
$st2->execute([':id' => $gameId]);
$genres = array_column($st2->fetchAll(), 'name');

$errors = [];
$notice = '';

// Handle Add to Cart (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $errors[] = 'Érvénytelen űrlap token.';
    }
    if (!($u = Auth::user())) {
        $errors[] = 'A kosár használatához jelentkezz be.';
    }

    $qty = (int)($_POST['quantity'] ?? 1);
    if ($qty < 1) $qty = 1;
    if ($qty > 99) $qty = 99;

    if (!$errors) {
        try {
            Cart::addItem((int)$u['user_id'], (int)$game['game_id'], $qty);
            // Redirect to cart to avoid resubmission
            header('Location: ' . base_url('cart.php'));
            exit;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$title  = 'Játék részletek';
$active = 'store';
include __DIR__ . '/partials/header.php';
?>
<?php
$sidebarTitle = 'Információ';
ob_start();
?>
<div>
  <p><strong>Fejlesztő/Kiadó:</strong> <?= e($game['publisher'] ?? '—') ?></p>
  <p><strong>Megjelenés:</strong> <?= e($game['release_date'] ?? '—') ?></p>
  <p><strong>Műfajok:</strong> <?= e(implode(', ', $genres)) ?></p>
  <p><strong>Ár:</strong> <?= number_format((float)$game['price'], 2, '.', ' ') ?> Ft</p>
</div>
<?php
$sidebarContent = ob_get_clean();
include __DIR__ . '/partials/sidebar-filters.php';
?>

<header class="content__header">
  <h1 class="content__title"><?= e($game['title']) ?></h1>
</header>

<article class="game">
  <div class="game__media">
    <?php if (!empty($game['image_url'])): ?>
      <img src="<?= e($game['image_url']) ?>" alt="<?= e($game['title']) ?>">
    <?php else: ?>
      <div class="card__placeholder" aria-hidden="true">No image</div>
    <?php endif; ?>
  </div>

  <div class="game__body">
    <h2>Leírás</h2>
    <p><?= nl2br(e($game['description'] ?? '')) ?></p>

    <?php if ($errors): ?>
      <div>
        <h3>Hibák:</h3>
        <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <section class="purchase">
      <h2>Vásárlás</h2>
      <p><strong>Ár:</strong> <?= number_format((float)$game['price'], 2, '.', ' ') ?> Ft</p>

      <form method="post" action="">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label for="qty">Mennyiség</label>
        <input id="qty" name="quantity" type="number" value="1" min="1" max="99">
        <button type="submit">Kosárba</button>
      </form>
    </section>
  </div>
</article>

<?php include __DIR__ . '/partials/footer.php'; ?>
