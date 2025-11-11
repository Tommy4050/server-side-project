<?php
require __DIR__ . '/../src/bootstrap.php';

$title  = 'Áruház';
$active = 'store';

// Filters
$search   = trim($_GET['q'] ?? '');
$priceMin = ($_GET['price_min'] ?? '') !== '' ? (float)$_GET['price_min'] : null;
$priceMax = ($_GET['price_max'] ?? '') !== '' ? (float)$_GET['price_max'] : null;
$genreId  = ($_GET['genre_id'] ?? '') !== '' ? (int)$_GET['genre_id'] : null;
$onSale   = isset($_GET['on_sale']) && $_GET['on_sale'] === '1';

$where   = ["g.is_published = 1"];
$params  = [];

if ($search !== '')      { $where[] = "g.title LIKE :q";        $params[':q']   = '%'.$search.'%'; }
if ($priceMin !== null)  { $where[] = "g.price >= :pmin";        $params[':pmin'] = $priceMin; }
if ($priceMax !== null)  { $where[] = "g.price <= :pmax";        $params[':pmax'] = $priceMax; }

$exists = '';
if ($genreId) {
  $exists = " AND EXISTS (SELECT 1 FROM game_genres x WHERE x.game_id = g.game_id AND x.genre_id = :gid)";
  $params[':gid'] = $genreId;
}

// Only show active sale items if requested
if ($onSale) {
  $where[] = "(g.sale_percent > 0
              AND (g.sale_start IS NULL OR g.sale_start <= CURRENT_DATE())
              AND (g.sale_end   IS NULL OR g.sale_end   >= CURRENT_DATE()))";
}

$whereSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

// Genres for filter
$genres = db()->query("SELECT genre_id, name FROM genres ORDER BY name")->fetchAll();

// Fetch games (include sale fields)
$sql = "
  SELECT
    g.game_id, g.title, g.price, g.image_url,
    g.sale_percent, g.sale_start, g.sale_end
  FROM games g
  $whereSql
  $exists
  ORDER BY g.created_at DESC, g.title ASC
  LIMIT 48
";
$st = db()->prepare($sql);
$st->execute($params);
$games = $st->fetchAll();

// Sidebar content (filters)
ob_start();
?>
<form class="filter filter--store" method="get" action="">
  <div class="filter__group">
    <label for="q">Keresés</label><br>
    <input id="q" name="q" type="text" value="<?= e($search) ?>">
  </div>

  <div class="filter__group">
    <label for="genre_id">Műfaj</label><br>
    <select id="genre_id" name="genre_id">
      <option value="">-- mindegy --</option>
      <?php foreach ($genres as $g): ?>
        <option value="<?= (int)$g['genre_id'] ?>"<?= ($genreId === (int)$g['genre_id'] ? ' selected' : '') ?>>
          <?= e($g['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="filter__group">
    <label for="price_min">Min. ár</label><br>
    <input id="price_min" name="price_min" type="number" step="0.01" value="<?= e($_GET['price_min'] ?? '') ?>">
  </div>

  <div class="filter__group">
    <label for="price_max">Max. ár</label><br>
    <input id="price_max" name="price_max" type="number" step="0.01" value="<?= e($_GET['price_max'] ?? '') ?>">
  </div>

  <div class="filter__group">
    <label>
      <input type="checkbox" name="on_sale" value="1" <?= $onSale ? 'checked' : '' ?>>
      Csak akciós
    </label>
  </div>

  <div class="filter__actions">
    <button type="submit">Szűrés</button>
    <a href="<?= e(base_url('store.php')) ?>">Szűrők törlése</a>
  </div>
</form>
<?php
$sidebarContent = ob_get_clean();
$sidebarTitle   = 'Áruház szűrők';

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar-filters.php';
?>

<header class="content__header">
  <h1 class="content__title">Áruház</h1>
  <p class="content__meta">Megjelenített játékok: <strong><?= count($games) ?></strong></p>
</header>

<?php if (!$games): ?>
  <div class="empty empty--store">
    <p>Nincs találat ezekkel a szűrőkkel.</p>
    <p><a href="<?= e(base_url('store.php')) ?>">Vissza az összes játékhoz</a></p>
  </div>
<?php else: ?>
  <section class="grid grid--store" aria-label="Játékok">
    <?php foreach ($games as $g): ?>
      <?php
        $activeSale = ((int)$g['sale_percent'] > 0)
          && (empty($g['sale_start']) || $g['sale_start'] <= date('Y-m-d'))
          && (empty($g['sale_end'])   || $g['sale_end']   >= date('Y-m-d'));

        $finalPrice = $g['price'];
        if ($activeSale && $g['price'] !== null) {
          $finalPrice = round(((float)$g['price']) * (100 - (int)$g['sale_percent']) / 100, 2);
        }
      ?>
      <article class="card card--game">
        <div class="card__media">
          <?php if (!empty($g['image_url'])): ?>
            <img src="<?= e($g['image_url']) ?>" alt="<?= e($g['title']) ?>">
          <?php else: ?>
            <div class="card__placeholder" aria-hidden="true">No image</div>
          <?php endif; ?>
        </div>
        <div class="card__body">
          <h3 class="card__title"><?= e($g['title']) ?></h3>
          <div class="card__meta">
            <?php if ($activeSale): ?>
              <span class="badge">-<?= (int)$g['sale_percent'] ?>%</span>
              <span style="text-decoration:line-through; opacity:.7; margin-left:6px;">
                <?= number_format((float)$g['price'], 2, '.', ' ') ?> Ft
              </span>
              <span style="font-weight:700; margin-left:6px;">
                <?= number_format((float)$finalPrice, 2, '.', ' ') ?> Ft
              </span>
            <?php else: ?>
              <span><?= number_format((float)$g['price'], 2, '.', ' ') ?> Ft</span>
            <?php endif; ?>
          </div>
          <div class="card__foot">
            <!-- Add to cart: returns to same page and auto-opens mini-cart -->
            <form method="post" action="<?= e(base_url('add_to_cart.php')) ?>" style="display:inline;">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="game_id" value="<?= (int)$g['game_id'] ?>">
              <input type="hidden" name="back_url" value="<?= e($_SERVER['REQUEST_URI']) ?>">
              <button type="submit">Kosárba</button>
            </form>
            &nbsp; <a class="card__link" href="<?= e(base_url('game.php')) . '?game_id=' . (int)$g['game_id'] ?>">Részletek</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
