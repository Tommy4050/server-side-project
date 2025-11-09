<?php
require __DIR__ . '/../../src/bootstrap.php';
require_admin();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? null)) {
    $gameId = (int)($_POST['game_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($gameId > 0) {
        if ($action === 'publish') {
            db()->prepare("UPDATE games SET is_published=1 WHERE game_id=:g")->execute([':g'=>$gameId]);
        } elseif ($action === 'unpublish') {
            db()->prepare("UPDATE games SET is_published=0 WHERE game_id=:g")->execute([':g'=>$gameId]);
        }
    }
    header('Location: ' . base_url('admin/games.php'));
    exit;
}

$rows = db()->query("SELECT game_id, title, price, is_published FROM games ORDER BY title")->fetchAll();

$title = 'Admin – Játékok';
$active = 'admin';

include __DIR__ . '/../partials/header.php';
?>
<main class="layout">
  <section class="content" aria-label="Tartalom">
    <header class="content__header">
      <h1 class="content__title">Játékok kezelése</h1>
      <p><a href="<?= e(base_url('admin/index.php')) ?>">← Vissza a vezérlőpulthoz</a></p>
    </header>

    <?php if (!$rows): ?>
      <p>Nincs játék.</p>
    <?php else: ?>
      <table border="1" cellpadding="6" cellspacing="0">
        <thead>
          <tr>
            <th>Cím</th>
            <th>Ár (Ft)</th>
            <th>Megjelenítés</th>
            <th>Művelet</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $g): ?>
            <tr>
              <td><?= e($g['title']) ?></td>
              <td><?= number_format((float)$g['price'], 0, '.', ' ') ?></td>
              <td><?= ((int)$g['is_published'] ? 'Igen' : 'Nem') ?></td>
              <td>
                <form method="post" action="" style="display:inline">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="game_id" value="<?= (int)$g['game_id'] ?>">
                  <?php if (!(int)$g['is_published']): ?>
                    <input type="hidden" name="action" value="publish">
                    <button type="submit">Megjelenít</button>
                  <?php else: ?>
                    <input type="hidden" name="action" value="unpublish">
                    <button type="submit">Elrejt</button>
                  <?php endif; ?>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
