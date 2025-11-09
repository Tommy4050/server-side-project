<?php
require __DIR__ . '/../../src/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? null)) {
    $uid = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($uid > 0) {
        if ($action === 'make_admin') {
            db()->prepare("UPDATE users SET is_admin=1 WHERE user_id=:u")->execute([':u'=>$uid]);
        } elseif ($action === 'remove_admin') {
            db()->prepare("UPDATE users SET is_admin=0 WHERE user_id=:u")->execute([':u'=>$uid]);
        }
    }
    header('Location: ' . base_url('admin/users.php'));
    exit;
}

$rows = db()->query("SELECT user_id, username, email, is_admin FROM users ORDER BY username")->fetchAll();

$title = 'Admin – Felhasználók';
$active = 'admin';

include __DIR__ . '/../partials/header.php';
?>
<main class="layout">
  <section class="content" aria-label="Tartalom">
    <header class="content__header">
      <h1 class="content__title">Felhasználók</h1>
      <p><a href="<?= e(base_url('admin/index.php')) ?>">← Vissza a vezérlőpulthoz</a></p>
    </header>

    <table border="1" cellpadding="6" cellspacing="0">
      <thead>
        <tr><th>Felhasználó</th><th>Email</th><th>Admin</th><th>Művelet</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $u): ?>
          <tr>
            <td><?= e($u['username']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= (int)$u['is_admin'] ? 'Igen' : 'Nem' ?></td>
            <td>
              <form method="post" action="" style="display:inline">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                <?php if (!(int)$u['is_admin']): ?>
                  <input type="hidden" name="action" value="make_admin">
                  <button type="submit">Adminná tesz</button>
                <?php else: ?>
                  <input type="hidden" name="action" value="remove_admin">
                  <button type="submit">Admin jog elvétele</button>
                <?php endif; ?>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
