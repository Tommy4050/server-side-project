<?php
require __DIR__ . '/../../src/bootstrap.php';
require_admin();

$errors = [];
$notice = '';

/** Helpers */
function fetch_genres(): array {
  try {
    return db()->query("SELECT genre_id, name FROM genres ORDER BY name")->fetchAll();
  } catch (Throwable $e) { return []; }
}
function set_game_genres(int $gameId, array $genreIds): void {
  try {
    db()->prepare("DELETE FROM game_genres WHERE game_id=:g")->execute([':g'=>$gameId]);
    if ($genreIds) {
      $st = db()->prepare("INSERT INTO game_genres (game_id, genre_id) VALUES (:g,:x)");
      foreach ($genreIds as $gid) $st->execute([':g'=>$gameId, ':x'=>(int)$gid]);
    }
  } catch (Throwable $e) { /* silently ignore if table not exists */ }
}
$allGenres = fetch_genres();

/** Actions: create / update / delete */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? null)) {
  $action = $_POST['action'] ?? '';
  try {
    if ($action === 'create' || $action === 'update') {
      $gameId = (int)($_POST['game_id'] ?? 0);
      $title  = trim($_POST['title'] ?? '');
      $publisher = trim($_POST['publisher'] ?? '');
      $release = trim($_POST['release_date'] ?? '');
      $price  = ($_POST['price'] ?? '') !== '' ? (float)$_POST['price'] : null;
      $desc   = trim($_POST['description'] ?? '');
      $isPub  = isset($_POST['is_published']) ? 1 : 0;
      $selGenres = array_map('intval', $_POST['genre_ids'] ?? []);

      // --- NEW: Sale fields
      $salePercent = (int)($_POST['sale_percent'] ?? 0);
      $saleStart   = trim($_POST['sale_start'] ?? '');
      $saleEnd     = trim($_POST['sale_end'] ?? '');

      // Basic validations
      if ($title === '') throw new RuntimeException('A cím kötelező.');
      if ($price !== null && $price < 0) throw new RuntimeException('Az ár nem lehet negatív.');

      // Parse dates
      // release date
      $releaseDt = null; $releaseIsFuture = false;
      if ($release !== '') {
        $releaseDt = DateTime::createFromFormat('Y-m-d', $release) ?: null;
        if ($releaseDt) {
          $today = new DateTime('today');
          $releaseIsFuture = ($releaseDt > $today);
        } else {
          throw new RuntimeException('Érvénytelen megjelenési dátum (YYYY-MM-DD).');
        }
      }

      // sale dates
      $saleStartDt = null; $saleEndDt = null;
      if ($saleStart !== '') {
        $saleStartDt = DateTime::createFromFormat('Y-m-d', $saleStart);
        if (!$saleStartDt) throw new RuntimeException('Érvénytelen akció kezdete (YYYY-MM-DD).');
      }
      if ($saleEnd !== '') {
        $saleEndDt = DateTime::createFromFormat('Y-m-d', $saleEnd);
        if (!$saleEndDt) throw new RuntimeException('Érvénytelen akció vége (YYYY-MM-DD).');
      }
      if ($saleStartDt && $saleEndDt && $saleEndDt < $saleStartDt) {
        throw new RuntimeException('Az akció vége nem lehet korábbi, mint a kezdete.');
      }
      if ($salePercent < 0 || $salePercent > 90) {
        throw new RuntimeException('Az akció mértéke 0 és 90% között lehet.');
      }

      // Publishing rule (same as before)
      if ($releaseIsFuture && $isPub) {
        throw new RuntimeException('Jövőbeli megjelenési dátummal a játék nem publikálható. '
          .'Vagy állítsd a dátumot múltra/jelenre, vagy vedd ki a „Publikálva” pipát.');
      }

      // Handle image upload if provided
      $imagePath = null;
      if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Kép feltöltési hiba.');
        if ($_FILES['image']['size'] > 5*1024*1024) throw new RuntimeException('Max 5 MB kép megengedett.');
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['image']['tmp_name']);
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
        if (!isset($allowed[$mime])) throw new RuntimeException('Csak JPG, PNG, GIF, WEBP tölthető fel.');
        $ext = $allowed[$mime];
        $subdir = date('Y/m');
        $destDir = __DIR__ . "/../uploads/games/$subdir";
        if (!is_dir($destDir) && !mkdir($destDir, 0775, true)) throw new RuntimeException('Könyvtár létrehozási hiba.');
        $basename = bin2hex(random_bytes(16));
        $filename = $basename.'.'.$ext;
        $fullpath = $destDir.'/'.$filename;
        if (!move_uploaded_file($_FILES['image']['tmp_name'],$fullpath)) throw new RuntimeException('Kép mentése sikertelen.');
        $imagePath = "/uploads/games/$subdir/$filename"; // public URL path
      }

      if ($action === 'create') {
        $st = db()->prepare("INSERT INTO games
          (title, description, publisher, release_date, price, sale_percent, sale_start, sale_end, image_url, is_published)
          VALUES (:t,:d,:p,:r,:pr,:sp,:ss,:se,:img,:pub)");
        $st->execute([
          ':t'=>$title,
          ':d'=>($desc!=='' ? $desc : null),
          ':p'=>($publisher!=='' ? $publisher : null),
          ':r'=>($release!=='' ? $release : null),
          ':pr'=>$price,
          ':sp'=>$salePercent,
          ':ss'=>($saleStart!=='' ? $saleStart : null),
          ':se'=>($saleEnd!=='' ? $saleEnd : null),
          ':img'=>$imagePath,
          ':pub'=>$isPub
        ]);
        $newId = (int)db()->lastInsertId();
        set_game_genres($newId, $selGenres);
        $notice = 'Játék hozzáadva.';

      } else { // update
        // Build SET dynamically to keep old image if new not provided
        $sets = "title=:t, description=:d, publisher=:p, release_date=:r, price=:pr,
                 sale_percent=:sp, sale_start=:ss, sale_end=:se, is_published=:pub";
        if ($imagePath !== null) $sets .= ", image_url=:img";
        $sql = "UPDATE games SET $sets WHERE game_id=:id";
        $st  = db()->prepare($sql);
        $st->bindValue(':t',$title);
        $st->bindValue(':d',($desc!=='' ? $desc : null));
        $st->bindValue(':p',($publisher!=='' ? $publisher : null));
        $st->bindValue(':r',($release!=='' ? $release : null));
        $st->bindValue(':pr',$price);
        $st->bindValue(':sp',$salePercent, PDO::PARAM_INT);
        $st->bindValue(':ss',($saleStart!=='' ? $saleStart : null));
        $st->bindValue(':se',($saleEnd!=='' ? $saleEnd : null));
        $st->bindValue(':pub',$isPub, PDO::PARAM_INT);
        if ($imagePath !== null) $st->bindValue(':img',$imagePath);
        $st->bindValue(':id',$gameId, PDO::PARAM_INT);
        $st->execute();
        set_game_genres($gameId, $selGenres);
        $notice = 'Játék frissítve.';
      }

    } elseif ($action === 'delete') {
      $id = (int)($_POST['game_id'] ?? 0);
      if ($id > 0) {
        db()->prepare("DELETE FROM games WHERE game_id=:id")->execute([':id'=>$id]);
        try { db()->prepare("DELETE FROM game_genres WHERE game_id=:id")->execute([':id'=>$id]); } catch (Throwable $e) {}
        $notice = 'Játék törölve.';
      }
    }
  } catch (Throwable $e) {
    $errors[] = $e->getMessage();
  }
}

/** Filters */
$titleLike = trim($_GET['title'] ?? '');
$publisher = trim($_GET['publisher'] ?? '');
$genreId   = ($_GET['genre_id'] ?? '') !== '' ? (int)$_GET['genre_id'] : null;

$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$per  = 20; $off = ($page-1)*$per;

/** WHERE */
$where=[]; $params=[]; $joinGenres=false;
if ($titleLike!==''){ $where[]="g.title LIKE :t";   $params[':t']="%$titleLike%"; }
if ($publisher!==''){ $where[]="g.publisher LIKE :pbl"; $params[':pbl']="%$publisher%"; }
if ($genreId){ $joinGenres=true; $where[]="gg.genre_id = :gid"; $params[':gid']=(int)$genreId; }
$whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

/** Count */
$sqlC = "SELECT COUNT(DISTINCT g.game_id)
         FROM games g ".($joinGenres ? "JOIN game_genres gg ON gg.game_id=g.game_id " : "")."
         $whereSql";
$stC = db()->prepare($sqlC);
foreach ($params as $k=>$v) {
  $stC->bindValue($k, $v, ($k === ':gid') ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stC->execute();
$total = (int)$stC->fetchColumn();
$pages = (int)max(1,ceil($total/$per));

/** Page */
$sql = "SELECT g.game_id, g.title, g.publisher, g.release_date, g.price,
               g.sale_percent, g.sale_start, g.sale_end,
               g.image_url, g.is_published
        FROM games g ".($joinGenres ? "JOIN game_genres gg ON gg.game_id=g.game_id " : "")."
        $whereSql
        GROUP BY g.game_id
        ORDER BY g.title
        LIMIT :lim OFFSET :off";
$st = db()->prepare($sql);
foreach ($params as $k=>$v) {
  $st->bindValue($k, $v, ($k === ':gid') ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$st->bindValue(':lim',$per,PDO::PARAM_INT);
$st->bindValue(':off',$off,PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll();

/** If editing, load current row + its genres */
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = null; $editGenres = [];
if ($editId) {
  $x = db()->prepare("SELECT * FROM games WHERE game_id=:id LIMIT 1");
  $x->execute([':id'=>$editId]); $editRow = $x->fetch();
  if ($editRow) {
    try {
      $gx = db()->prepare("SELECT genre_id FROM game_genres WHERE game_id=:id");
      $gx->execute([':id'=>$editId]); $editGenres = array_map('intval', array_column($gx->fetchAll(),'genre_id'));
    } catch (Throwable $e) {}
  }
}

/** Determine if edit row has future release (for disabling publish checkbox) */
$editReleaseFuture = false;
if (!empty($editRow['release_date'])) {
  $dt = DateTime::createFromFormat('Y-m-d', $editRow['release_date']) ?: null;
  if ($dt) {
    $editReleaseFuture = ($dt > new DateTime('today'));
  }
}

// Helper: human sale active?
function is_sale_active_row(array $g): bool {
  if (empty($g['sale_percent']) || (int)$g['sale_percent'] <= 0) return false;
  $today = new DateTime('today');
  if (!empty($g['sale_start'])) {
    $ss = DateTime::createFromFormat('Y-m-d', $g['sale_start']); if ($ss && $today < $ss) return false;
  }
  if (!empty($g['sale_end'])) {
    $se = DateTime::createFromFormat('Y-m-d', $g['sale_end']); if ($se && $today > $se) return false;
  }
  return true;
}

$title='Admin – Játékok';
$active='admin';
include __DIR__ . '/../partials/header.php';
?>
<main class="layout">
  <section class="content" aria-label="Tartalom">
    <header class="content__header">
      <h1 class="content__title">Játékok kezelése</h1>
      <p><a href="<?= e(base_url('admin/index.php')) ?>">← Vissza a vezérlőpulthoz</a></p>
    </header>

    <?php if ($notice): ?><p style="color:green;"><?= e($notice) ?></p><?php endif; ?>
    <?php if ($errors): ?>
      <div><h3>Hibák:</h3><ul><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <h2>Keresés</h2>
    <form method="get" action="" style="margin-bottom:12px;">
      <label>Cím: <input name="title" type="text" value="<?= e($titleLike) ?>"></label>
      <label>Kiadó: <input name="publisher" type="text" value="<?= e($publisher) ?>"></label>
      <?php if ($allGenres): ?>
        <label>Műfaj:
          <select name="genre_id">
            <option value="">-- mindegy --</option>
            <?php foreach ($allGenres as $g): ?>
              <option value="<?= (int)$g['genre_id'] ?>"<?= $genreId===(int)$g['genre_id']?' selected':'' ?>><?= e($g['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      <?php endif; ?>
      <button type="submit">Szűrés</button>
      <a href="<?= e(base_url('admin/games.php')) ?>">Töröl</a>
    </form>

    <h2><?= $editRow ? 'Játék szerkesztése' : 'Új játék hozzáadása' ?></h2>
    <form method="post" action="" enctype="multipart/form-data" style="border:1px solid #ddd; padding:10px; margin-bottom:16px;">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'create' ?>">
      <?php if ($editRow): ?><input type="hidden" name="game_id" value="<?= (int)$editRow['game_id'] ?>"><?php endif; ?>

      <div><label>Cím*<br><input name="title" type="text" required value="<?= e($editRow['title'] ?? '') ?>"></label></div>
      <div><label>Kiadó<br><input name="publisher" type="text" value="<?= e($editRow['publisher'] ?? '') ?>"></label></div>
      <div><label>Megjelenés dátuma<br><input name="release_date" type="date" value="<?= e($editRow['release_date'] ?? '') ?>"></label></div>
      <div><label>Ár (Ft)<br><input name="price" type="number" step="0.01" value="<?= e($editRow['price'] ?? '') ?>"></label></div>
      <div><label>Leírás<br><textarea name="description" rows="4" cols="60"><?= e($editRow['description'] ?? '') ?></textarea></label></div>

      <?php if ($allGenres): ?>
        <div>
          <label>Műfajok (Ctrl/⌘ több kiválasztás):<br>
            <select name="genre_ids[]" multiple size="5" style="min-width:220px;">
              <?php foreach ($allGenres as $g): $sel = in_array((int)$g['genre_id'], $editGenres ?? [], true); ?>
                <option value="<?= (int)$g['genre_id'] ?>"<?= $sel?' selected':'' ?>><?= e($g['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>
      <?php endif; ?>

      <fieldset style="border:1px solid #ccc; padding:8px; margin-top:8px;">
        <legend>Akció</legend>
        <div><label>Akció mértéke (%)<br><input name="sale_percent" type="number" min="0" max="90" value="<?= e($editRow['sale_percent'] ?? 0) ?>"></label></div>
        <div><label>Kezdete<br><input name="sale_start" type="date" value="<?= e($editRow['sale_start'] ?? '') ?>"></label></div>
        <div><label>Vége<br><input name="sale_end" type="date" value="<?= e($editRow['sale_end'] ?? '') ?>"></label></div>
        <small>0% = nincs akció. Ha dátumok üresek, az akció folyamatos (amíg a százalék > 0).</small>
      </fieldset>

      <div><label>Borítókép (JPG/PNG/GIF/WEBP, max 5MB)<br><input type="file" name="image" accept=".jpg,.jpeg,.png,.gif,.webp"></label></div>
      <?php if (!empty($editRow['image_url'])): ?>
        <p>Jelenlegi: <img src="<?= e(asset_url($editRow['image_url'])) ?>" alt="" style="max-height:70px;"></p>
      <?php endif; ?>

      <div>
        <label>
          <input
            type="checkbox"
            name="is_published"
            <?= !empty($editRow) && (int)$editRow['is_published'] && !$editReleaseFuture ? 'checked' : '' ?>
            <?= $editReleaseFuture ? 'disabled' : '' ?>
          >
          Publikálva
        </label>
        <small style="display:block; color:#a00; margin-top:4px;">
          Jövőbeli megjelenési dátum esetén nem publikálható.
        </small>
      </div>

      <div style="margin-top:8px;">
        <button type="submit"><?= $editRow ? 'Mentés' : 'Hozzáadás' ?></button>
        <?php if ($editRow): ?>
          <a href="<?= e(base_url('admin/games.php')) ?>">Mégse</a>
        <?php endif; ?>
      </div>
    </form>

    <h2>Találatok (<?= (int)$total ?>)</h2>
    <?php if (!$rows): ?>
      <p>Nincs találat.</p>
    <?php else: ?>
      <table border="1" cellpadding="6" cellspacing="0">
        <thead><tr>
          <th>Kép</th><th>Cím</th><th>Kiadó</th><th>Megjelenés</th>
          <th>Ár</th><th>Akció</th><th>Publikus</th><th>Műveletek</th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $g): ?>
          <?php
            $active = is_sale_active_row($g);
            $finalPrice = $g['price'];
            if ($active && $g['price'] !== null) {
              $finalPrice = round(((float)$g['price']) * (100 - (int)$g['sale_percent']) / 100, 2);
            }
          ?>
          <tr>
            <td><?php if (!empty($g['image_url'])): ?><img src="<?= e(asset_url($g['image_url'])) ?>" alt="" style="max-height:50px;"><?php endif; ?></td>
            <td><?= e($g['title']) ?></td>
            <td><?= e($g['publisher'] ?? '') ?></td>
            <td><?= e($g['release_date'] ?? '') ?></td>
            <td>
              <?php if ($g['price'] !== null): ?>
                <?php if ($active): ?>
                  <span style="text-decoration:line-through; opacity:.7;"><?= number_format((float)$g['price'], 2, '.', ' ') ?> Ft</span>
                  <strong style="margin-left:6px;"><?= number_format((float)$finalPrice, 2, '.', ' ') ?> Ft</strong>
                <?php else: ?>
                  <?= number_format((float)$g['price'], 2, '.', ' ') ?> Ft
                <?php endif; ?>
              <?php else: ?>-<?php endif; ?>
            </td>
            <td>
              <?= (int)$g['sale_percent'] ?>%
              <?php if (!empty($g['sale_start']) || !empty($g['sale_end'])): ?>
                <br><small><?= e(($g['sale_start'] ?: '—').' → '.($g['sale_end'] ?: '—')) ?></small>
              <?php endif; ?>
              <?php if ($active): ?><br><span style="color:green;">AKTÍV</span><?php endif; ?>
            </td>
            <td><?= (int)$g['is_published'] ? 'Igen' : 'Nem' ?></td>
            <td>
              <a href="<?= e(base_url('admin/games.php')) . '?edit=' . (int)$g['game_id'] ?>">Szerkeszt</a>
              <form method="post" action="" style="display:inline" onsubmit="return confirm('Biztosan törlöd?');">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="game_id" value="<?= (int)$g['game_id'] ?>">
                <button type="submit">Töröl</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

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
