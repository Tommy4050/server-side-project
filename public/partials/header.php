<?php
// Usage: set $title and $active before including this file
// $active can be 'store', 'library', 'community', 'profile'
$u = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? e($title) : 'App' ?></title>

    <!-- Minimal styles for mini-cart -->
    <style>
      .mini-cart {
        position: fixed;
        top: 12px;
        right: 12px;
        width: 360px;
        max-height: 80vh;
        background: #fff;
        border: 1px solid #ddd;
        box-shadow: 0 10px 30px rgba(0,0,0,.1);
        border-radius: 8px;
        display: none;
        flex-direction: column;
        z-index: 1000;
      }
      .mini-cart.is-open { display: flex; }
      .mini-cart__head, .mini-cart__foot { padding: 10px 12px; border-bottom: 1px solid #eee; }
      .mini-cart__foot { border-top: 1px solid #eee; border-bottom: 0; }
      .mini-cart__close { float: right; border: 0; background: transparent; font-size: 20px; line-height: 1; cursor: pointer; }
      .mini-cart__body { padding: 8px 12px; overflow: auto; }
      .mini-cart__list { list-style: none; margin: 0; padding: 0; }
      .mini-cart__item { display: grid; grid-template-columns: 56px 1fr auto; gap: 8px; align-items: center; padding: 8px 0; border-bottom: 1px solid #f3f3f3; }
      .mini-cart__thumb img { width: 56px; height: 36px; object-fit: cover; border-radius: 4px; }
      .mini-cart__placeholder { width:56px; height:36px; background:#f2f2f2; border-radius:4px; display:flex; align-items:center; justify-content:center; font-size:11px; color:#777; }
      .mini-cart__title { font-weight:600; }
      .mini-cart__meta { display:flex; gap:8px; align-items:center; margin-top:6px; flex-wrap: wrap; }
      .mini-cart__form { display:inline-flex; gap:6px; align-items:center; }
      .mini-cart__price { white-space: nowrap; font-weight:600; }
      .mini-cart__total { font-size: 14px; }
      .mini-cart__actions { display:flex; gap:10px; justify-content:flex-end; }
      .nav__link--cart { cursor:pointer; background:none; border:0; padding:0; }
    </style>
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
          <?php if ($u): ?>
            <a class="nav__link" href="<?= e(base_url('profile.php')) ?>"><?= e($u['username']) ?></a>
            &nbsp;|&nbsp; <a class="nav__link" href="<?= e(base_url('friends.php')) ?>">Barátaim</a>
            &nbsp;|&nbsp; <a class="nav__link" href="<?= e(base_url('upload.php')) ?>">Feltöltés</a>
            &nbsp;|&nbsp;
            <?php
              // Mini cart count
              $cartCount = 0;
              try {
                require_once __DIR__ . '/../../src/Cart.php';
                $miniInfo = Cart::mini((int)$u['user_id']);
                $cartCount = (int)($miniInfo['count'] ?? 0);
              } catch (Throwable $e) {
                $cartCount = 0;
              }
            ?>
            <button type="button" id="miniCartToggle" class="nav__link nav__link--cart" aria-expanded="false">
              Kosár (<?= (int)$cartCount ?>)
            </button>

            &nbsp;|&nbsp; <a class="nav__link" href="<?= e(base_url('orders.php')) ?>">Rendeléseim</a>
            <?php if (function_exists('is_admin') && is_admin()): ?>
              &nbsp;|&nbsp; <a class="nav__link" href="<?= e(base_url('admin/index.php')) ?>">Admin</a>
            <?php endif; ?>

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
          <li class="nav__item">
            <?php $onSale = isset($_GET['on_sale']) && $_GET['on_sale'] === '1'; ?>
            <a class="nav__link" href="<?= e(base_url('store.php')) ?>?on_sale=1" <?= $onSale ? 'aria-current="page"' : '' ?>>Akciók</a>
            <?php if ($onSale): ?>
              &nbsp;|&nbsp; <a class="nav__link" href="<?= e(base_url('store.php')) ?>">Összes</a>
            <?php endif; ?>
          </li>
        </ul>
      </nav>
    </div>
  <?php endif; ?>

</header>

<?php
// Include the mini cart popup element right after the header
include __DIR__ . '/cart-mini.php';
?>

<!-- Tiny JS toggler for the mini cart -->
<script>
(function(){
  const toggle = document.getElementById('miniCartToggle');
  const panel  = document.getElementById('miniCart');
  const close  = panel ? panel.querySelector('.mini-cart__close') : null;

  function openPanel() {
    if (!panel) return;
    panel.classList.add('is-open');
    if (toggle) toggle.setAttribute('aria-expanded', 'true');
  }
  function closePanel() {
    if (!panel) return;
    panel.classList.remove('is-open');
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
  }
  function onDocClick(e) {
    if (!panel || !panel.classList.contains('is-open')) return;
    if (panel.contains(e.target) || (toggle && toggle.contains(e.target))) return;
    closePanel();
  }

  if (toggle && panel) {
    toggle.addEventListener('click', function(){
      const open = panel.classList.contains('is-open');
      open ? closePanel() : openPanel();
    });
  }
  if (close) close.addEventListener('click', closePanel);
  document.addEventListener('click', onDocClick);

  // >>> NEW: auto-open if ?cart_open=1 is present
  try {
    const params = new URLSearchParams(window.location.search);
    if (params.get('cart_open') === '1') {
      openPanel();
      // optionally remove the param from the URL without reloading:
      params.delete('cart_open');
      const clean = window.location.pathname + (params.toString() ? '?' + params.toString() : '') + window.location.hash;
      window.history.replaceState({}, '', clean);
    }
  } catch(e) {}
})();
</script>


<main class="layout">
  <!-- Left sidebar + main content column layout -->
  <aside class="sidebar" aria-label="Oldalsáv">
