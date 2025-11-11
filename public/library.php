<?php
require __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Library.php';
require_once __DIR__ . '/../src/Play.php';

$me = Auth::user();
if (!$me) redirect(base_url('login.php'));

$genres = Library::allGenres();

$titleLike = trim($_GET['title'] ?? '');
$genreId   = ($_GET['genre_id'] ?? '') !== '' ? (int)$_GET['genre_id'] : null;
$priceMin  = ($_GET['price_min'] ?? '') !== '' ? (float)$_GET['price_min'] : null;
$priceMax  = ($_GET['price_max'] ?? '') !== '' ? (float)$_GET['price_max'] : null;

$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;

$result = Library::getUserLibrary(
  (int)$me['user_id'],
  $titleLike ?: null,
  $genreId,
  $priceMin,
  $priceMax,
  $page,
  $perPage
);

$total = $result['total'];
$rows  = $result['rows'];
$pages = (int)max(1, ceil($total / $perPage));

$title  = 'Könyvtár';
$active = 'library';

// Active play session (if any)
$activeSession = Play::activeSessionForUser((int)$me['user_id']);

/** Pretty playtime: <60 mins => "X perc", else "H, d óra" with comma */
function pretty_playtime(int $seconds): string {
  if ($seconds < 3600) {
    $mins = (int) round($seconds / 60);
    if ($mins < 1) $mins = 1; // show at least 1 minute if there was any play
    return $mins . ' perc';
  }
  $hours = $seconds / 3600;
  // 1 decimal, comma as decimal separator, no thousands separator
  return number_format($hours, 1, ',', '') . ' óra';
}


// Optional error message via GET (from library_play.php redirect)
$inlineError = trim($_GET['error'] ?? '');

// Build sidebar form (plain HTML) to inject into the layout
ob_start();
?>
<form class="filter filter--library" method="get" action="">
  <div class="filter__group">
    <label for="title">Keresés cím szerint</label><br>
    <input id="title" name="title" type="text" value="<?= e($titleLike) ?>">
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
    <a href="<?= e(base_url('library.php')) ?>">Szűrők törlése</a>
  </div>
</form>
<?php
$sidebarContent = ob_get_clean();
$sidebarTitle   = 'Könyvtár szűrők';

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar-filters.php';
?>

<header class="content__header">
  <h1 class="content__title">Saját könyvtár</h1>
  <p class="content__meta">Találatok: <strong><?= (int)$total ?></strong> · Oldal: <strong><?= (int)$page ?>/<?= (int)$pages ?></strong></p>
  <?php if ($inlineError !== ''): ?>
    <p style="color:#a00;"><?= e($inlineError) ?></p>
  <?php endif; ?>
</header>

<?php if ($total === 0): ?>
  <div class="empty empty--library">
    <p>Még nincs játék a könyvtáradban.</p>
    <p><a href="<?= e(base_url('store.php')) ?>">Nézd meg az áruházat</a></p>
  </div>
<?php else: ?>
  <section class="grid grid--library" aria-label="Játékok listája">
    <?php foreach ($rows as $r): ?>
      <?php
        $isActiveForThis = $activeSession && (int)$activeSession['game_id'] === (int)$r['game_id'];
        $anotherActive   = $activeSession && !$isActiveForThis;
        $totalSecs       = (int)($r['total_play_seconds'] ?? 0);
      ?>
      <article class="card card--game">
        <div class="card__media">
          <?php if (!empty($r['image_url'])): ?>
            <a href="<?= e(base_url('library_game.php')) . '?game_id=' . (int)$r['game_id'] ?>">
              <img src="<?= e($r['image_url']) ?>" alt="<?= e($r['title']) ?>">
            </a>
          <?php else: ?>
            <div class="card__placeholder" aria-hidden="true">No image</div>
          <?php endif; ?>
        </div>

        <div class="card__body">
          <h3 class="card__title">
            <a href="<?= e(base_url('my_game.php')) . '?game_id=' . (int)$r['game_id'] ?>">
              <?= e($r['title']) ?>
            </a>
          </h3>
          <div class="card__meta">
            <span class="card__genres"><?= e($r['genres'] ?? '') ?></span>
            <!-- <span class="card__price"><?= isset($r['price']) ? number_format((float)$r['price'], 2, '.', ' ') . ' Ft' : '' ?></span> -->
          </div>

          <div class="card__foot">
            <small class="card__acquired">
              Hozzáadva: <?= e($r['acquired_at']) ?> · Forrás: <?= e($r['source']) ?>
              <br>Összes játékidő: <strong><?= pretty_playtime($totalSecs) ?></strong>
              <?php if ($isActiveForThis): ?>
                <br><em>Jelenleg fut (kezdete: <?= e($activeSession['started_at']) ?>)</em>
              <?php elseif ($anotherActive): ?>
                <br><em>Másik játék már fut — előbb állítsd le azt.</em>
              <?php endif; ?>
            </small>
            &nbsp;·&nbsp;
            <a href="<?= e(base_url('library_game.php')) . '?game_id=' . (int)$r['game_id'] ?>">
              Közösségi posztok
            </a>

            <form method="post" action="<?= e(base_url('library_play.php')) ?>" style="margin-top:6px;">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="game_id" value="<?= (int)$r['game_id'] ?>">
              <?php if ($isActiveForThis): ?>
                <input type="hidden" name="action" value="stop">
                <button type="submit">Leállítás</button>
              <?php else: ?>
                <input type="hidden" name="action" value="start">
                <button type="submit" <?= $anotherActive ? 'disabled' : '' ?>>Játék indítása</button>
              <?php endif; ?>
            </form>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </section>

  <nav class="pagination" aria-label="Lapozás">
    <ul class="pagination__list">
      <li class="pagination__item">
        <?php if ($page > 1): ?>
          <a class="pagination__link" href="?<?= e(http_build_query(array_merge($_GET, ['page' => $page - 1]))) ?>">&laquo; Előző</a>
        <?php else: ?>
          <span class="pagination__link pagination__link--disabled">&laquo; Előző</span>
        <?php endif; ?>
      </li>
      <li class="pagination__item">
        <?php if ($page < $pages): ?>
          <a class="pagination__link" href="?<?= e(http_build_query(array_merge($_GET, ['page' => $page + 1]))) ?>">Következő &raquo;</a>
        <?php else: ?>
          <span class="pagination__link pagination__link--disabled">Következő &raquo;</span>
        <?php endif; ?>
      </li>
    </ul>
  </nav>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
