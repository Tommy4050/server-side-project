<?php
require __DIR__ . '/../src/bootstrap.php';

$me = Auth::user();
if (!$me) redirect(base_url('login.php'));

$errors = [];
$notice = '';

$games = db()->query("SELECT game_id, title FROM games WHERE is_published=1 ORDER BY title")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) $errors[] = 'Érvénytelen űrlap token.';
    $gameId  = isset($_POST['game_id']) && $_POST['game_id'] !== '' ? (int)$_POST['game_id'] : null;
    $caption = trim($_POST['caption'] ?? '');

    // File checks
    // File checks
    if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
        $errors[] = 'Kép feltöltése kötelező.';
    } else {
        $f = $_FILES['image'];

        // Handle PHP upload errors more explicitly
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $errors[] = match ($f['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'A kép túl nagy.',
                UPLOAD_ERR_PARTIAL => 'A fájl feltöltése nem fejeződött be.',
                UPLOAD_ERR_NO_FILE => 'Nem választottál fájlt.',
                default => 'Feltöltési hiba történt.',
            };
        } else {
            if ($f['size'] > 5 * 1024 * 1024) {
                $errors[] = 'A kép mérete legfeljebb 5 MB lehet.';
            } else {
                // MIME check (allow WEBP too)
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($f['tmp_name']);

                // Allow-list of MIME types -> extension
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/gif'  => 'gif',
                    'image/webp' => 'webp', // <-- add this
                ];

                // Fallback: sometimes finfo may return generic types; try exif_imagetype if available
                if (!isset($allowed[$mime]) && function_exists('exif_imagetype')) {
                    $type = @exif_imagetype($f['tmp_name']);
                    $map  = [
                        IMAGETYPE_JPEG => 'image/jpeg',
                        IMAGETYPE_PNG  => 'image/png',
                        IMAGETYPE_GIF  => 'image/gif',
                        IMAGETYPE_WEBP => 'image/webp',
                    ];
                    if ($type && isset($map[$type])) {
                        $mime = $map[$type];
                    }
                }

                if (!isset($allowed[$mime])) {
                    $errors[] = 'Csak JPG, PNG, GIF vagy WEBP tölthető fel.';
                }
            }
        }
    }

    if (!$errors) {
        $ext     = $allowed[$mime];
        $subdir  = date('Y/m'); // e.g. 2025/11
        $baseDir = __DIR__ . '/uploads';
        $destDir = $baseDir . '/' . $subdir;

        // 1) Ensure base uploads dir exists
        if (!is_dir($baseDir) && !@mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
            $errors[] = 'Nem tudtam létrehozni az alap feltöltési mappát: ' . $baseDir . ' (írási jogosultság?)';
        }

        // 2) Ensure year/month subdir exists (2025/11)
        if (!$errors && !is_dir($destDir) && !@mkdir($destDir, 0775, true) && !is_dir($destDir)) {
            $errors[] = 'Nem tudtam létrehozni a feltöltési mappát: ' . $destDir . ' (írási jogosultság?)';
        }

        $basename = bin2hex(random_bytes(16));
        $filename = $basename . '.' . $ext;
        $fullpath = $destDir . '/' . $filename;

        // Extra sanity: the final directory must exist and be writable
        if (!$errors && (!is_dir($destDir) || !is_writable($destDir))) {
            $errors[] = 'A célkönyvtár nem írható: ' . $destDir . ' (jogosultságok?)';
        }

        // 3) Only move if all dirs are OK
        if (!$errors) {
            if (!@move_uploaded_file($f['tmp_name'], $fullpath)) {
                $errors[] = 'Nem sikerült a fájl mentése. Ellenőrizd a jogosultságokat: ' . dirname($fullpath);
            } else {
                // Web path stored in DB (note: leading slash so it’s web-accessible)
                $webPath = "uploads/$subdir/$filename"; // no leading slash
                try {
                    $postId = Post::create((int)$me['user_id'], $gameId, $caption, $webPath);
                    header('Location: ' . base_url('feed.php'));
                    exit;
                } catch (Throwable $e) {
                    $errors[] = $e->getMessage();
                    @unlink($fullpath);
                }
            }
        }

    }
}
$title = 'Kép feltöltése';
$active = 'community';
include __DIR__ . '/partials/header.php';

$sidebarTitle = 'Szabályok';
ob_start(); ?>
<ul>
  <li>Csak saját készítésű képeket tölts fel.</li>
  <li>Megengedett formátumok: JPG, PNG, GIF, WEBP. Max 5 MB.</li>
</ul>
<?php
$sidebarContent = ob_get_clean();
include __DIR__ . '/partials/sidebar-filters.php';
?>
<header class="content__header">
  <h1 class="content__title">Képfeltöltés</h1>
</header>

<?php if ($errors): ?>
  <div>
    <h3>Hibák:</h3>
    <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<form method="post" action="" enctype="multipart/form-data">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

  <div>
    <label for="game_id">Játék (opcionális)</label><br>
    <select id="game_id" name="game_id">
      <option value="">-- nincs megadva --</option>
      <?php foreach ($games as $g): ?>
        <option value="<?= (int)$g['game_id'] ?>"><?= e($g['title']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div>
    <label for="caption">Leírás (opcionális)</label><br>
    <textarea id="caption" name="caption" rows="3" cols="60" maxlength="500"></textarea>
  </div>

  <div>
    <label for="image">Kép</label><br>
    <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.gif,.webp" required>
  </div>

  <button type="submit">Feltöltés</button>
</form>

<?php include __DIR__ . '/partials/footer.php'; ?>
