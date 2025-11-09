<?php
// Usage: set $title and $active before including this file
// $active can be 'store', 'library', 'community', 'profile'
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? e($title) : 'App' ?></title>
</head>
<body class="page">
<header class="header">
  <div class="header__inner">
    <div class="brand">
      <a class="brand__link" href="<?= e(base_url('index.php')) ?>">GameShop</a>
    </div>

    <nav class="nav nav--primary" aria-label="Fő navigáció">
      <ul class="nav__list">
        <li class="nav__item<?= ($active ?? '') === 'store' ? ' nav__item--active' : '' ?>">
          <a class="nav__link" href="<?= e(base_url('store.php')) ?>">Áruház</a>
        </li>
        <li class="nav__item<?= ($active ?? '') === 'library' ? ' nav__item--active' : '' ?>">
          <a class="nav__link" href="<?= e(base_url('library.php')) ?>">Könyvtár</a>
        </li>
        <li class="nav__item<?= ($active ?? '') === 'community' ? ' nav__item--active' : '' ?>">
          <a class="nav__link" href="<?= e(base_url('feed.php')) ?>">Közösség</a>
        </li>

        <li class="nav__item nav__item--right <?= ($active ?? '') === 'profile' ? ' nav__item--active' : '' ?>">
          <?php if ($u = Auth::user()): ?>
            <a class="nav__link" href="<?= e(base_url('profile.php')) ?>"><?= e($u['username']) ?></a>
            &nbsp;|&nbsp; <a class="nav__link" href="<?= e(base_url('upload.php')) ?>">Feltöltés</a>
            &nbsp;|&nbsp; <a class="nav__link" href="<?= e(base_url('cart.php')) ?>">
              Kosár (<?= (int)Cart::itemCount((int)$u['user_id']) ?>)
            </a>
            &nbsp;|&nbsp; <a class="nav__link" href="<?= e(base_url('orders.php')) ?>">Rendeléseim</a>
          <?php else: ?>
            <a class="nav__link" href="<?= e(base_url('login.php')) ?>">Bejelentkezés</a>
          <?php endif; ?>
        </li>

      </ul>
    </nav>
  </div>

  <?php if (($active ?? '') === 'store'): ?>
    <div class="subnav" aria-label="Áruház al-navigáció">
      <nav class="nav nav--sub">
        <ul class="nav__list">
          <li class="nav__item"><a class="nav__link" href="<?= e(base_url('store.php')) ?>">Kiemeltek</a></li>
          <li class="nav__item"><a class="nav__link" href="<?= e(base_url('store.php')) ?>?sort=new">Újdonságok</a></li>
          <li class="nav__item"><a class="nav__link" href="<?= e(base_url('store.php')) ?>?sort=top">Top eladások</a></li>
          <li class="nav__item"><a class="nav__link" href="<?= e(base_url('store.php')) ?>?discount=1">Akciók</a></li>
        </ul>
      </nav>
    </div>
  <?php endif; ?>

</header>

<main class="layout">
  <!-- Left sidebar + main content column layout -->
  <aside class="sidebar" aria-label="Oldalsáv">
</html>