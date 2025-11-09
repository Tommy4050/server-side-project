<?php
require __DIR__ . '/../../src/bootstrap.php';
require_admin();

$title = 'Admin – Vezérlőpult';
$active = 'admin';

include __DIR__ . '/../partials/header.php';
?>
<main class="layout">
  <section class="content" aria-label="Tartalom">
    <header class="content__header">
      <h1 class="content__title">Admin vezérlőpult</h1>
    </header>

    <ul>
      <li><a href="<?= e(base_url('admin/posts.php')) ?>">Posztok moderálása</a></li>
      <li><a href="<?= e(base_url('admin/games.php')) ?>">Játékok kezelése</a></li>
      <li><a href="<?= e(base_url('friends.php')) ?>">Barátkezelés (felhasználói nézet)</a></li>
    </ul>
  </section>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
