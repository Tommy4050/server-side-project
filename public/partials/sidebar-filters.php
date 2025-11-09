<?php
// Expect $sidebarTitle and $sidebarContent (HTML string) provided by the page
?>
  <section class="sidebar__section">
    <h2 class="sidebar__title"><?= e($sidebarTitle ?? 'Szűrők') ?></h2>
    <div class="sidebar__content">
      <?= $sidebarContent ?? '' ?>
    </div>
  </section>
</aside>

<section class="content" aria-label="Tartalom">
  <!-- Page main body starts here -->
