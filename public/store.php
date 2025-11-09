<?php
require __DIR__ . '/../src/bootstrap.php';

$title  = 'Áruház';
$active = 'store';

// Minimal data fetch (published games)
$search   = trim($_GET['q'] ?? '');
$priceMin = ($_GET['price_min'] ?? '') !== '' ? (float)$_GET['price_min'] : null;
$priceMax = ($_GET['price_max'] ?? '') !== '' ? (float)$_GET['price_max'] : null;
$genreId  = ($_GET['genre_id'] ?? '') !== '' ? (int)$_GET['genre_id'] : null;

$where = ["g.is_published = 1"];
$params = [];

if ($search !== '') { $where[] = "g.title LIKE :q"; $params[':q'] = '%'.$search.'%'; }
if ($priceMin !== null) { $where[] = "g.price >= :pmin"; $params[':pmin'] = $priceMin; }
if ($priceMax !== null) { $where[] = "g.price <= :pmax"; $params[':pmax'] = $priceMax; }
$exists = '';
if ($genreId) {
  $exists = " AND EXISTS (SELECT 1 FROM game_genres x WHERE x.game_id = g.game_id AND x.genre_id = :gid)";
  $params[':gid'] = $genreId;
}

$whereSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

$genres = db()->query("SELECT genre_id, name FROM genres ORDER BY name")->fetchAll();

$sql = "
  SELECT g.game_id, g.title, g.price, g.image_url
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
            <span class="card__price"><?= number_format((float)$g['price'], 2, '.', ' ') ?> Ft</span>
          </div>
          <div class="card__foot">
            <!-- Later: Add to cart button (POST) -->
            <a class="card__link" href="<?= e(base_url('game.php')) . '?game_id=' . (int)$g['game_id'] ?>">Részletek</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
