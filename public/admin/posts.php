<?php
require __DIR__ . '/../../src/bootstrap.php';
require_admin();

$me = Auth::user();
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$per  = 12;

$res   = Post::feedWithTotal($me['user_id'] ?? null, null, null, $page, $per, /*isAdmin*/ true);
$rows  = $res['rows'];
$total = $res['total'];
$pages = (int)max(1, ceil($total / $per));

$title = 'Admin – Poszt moderáció';
$active = 'admin';

include __DIR__ . '/../partials/header.php';
?>
<main class="layout">
  <section class="content" aria-label="Tartalom">
    <header class="content__header">
      <h1 class="content__title">Posztok moderálása</h1>
      <p class="content__meta">Találatok: <strong><?= (int)$total ?></strong> · Oldal: <strong><?= (int)$page ?>/<?= (int)$pages ?></strong></p>
      <p><a href="<?= e(base_url('admin/index.php')) ?>">← Vissza a vezérlőpulthoz</a></p>
    </header>

    <?php if (!$rows): ?>
      <p>Nincs poszt.</p>
    <?php else: ?>
      <?php foreach ($rows as $p): ?>
        <article style="border:1px solid #ddd; padding:8px; margin-bottom:12px;">
          <header>
            <strong><?= e($p['username']) ?></strong>
            <?php if (!empty($p['game_title'])): ?> · <em><?= e($p['game_title']) ?></em><?php endif; ?>
            · <small><?= e($p['created_at']) ?></small>
            <?php if ($p['is_hidden']): ?><span> · <em>Rejtett</em></span><?php endif; ?>
          </header>
          <div style="margin:8px 0;">
            <img src="<?= e(asset_url($p['image_path'])) ?>" alt="" style="max-width:100%; height:auto">
          </div>
          <?php if (!empty($p['caption'])): ?>
            <p><?= nl2br(e($p['caption'])) ?></p>
          <?php endif; ?>

          <form method="post" action="<?= e(base_url('feed.php')) ?>" style="display:inline">
            <!-- Reuse feed actions: it already supports hide/unhide for admins -->
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="post_id" value="<?= (int)$p['post_id'] ?>">
            <?php if (!$p['is_hidden']): ?>
              <input type="hidden" name="action" value="hide_post">
              <input type="text" name="reason" placeholder="indok" style="width:160px">
              <button type="submit">Elrejtés</button>
            <?php else: ?>
              <input type="hidden" name="action" value="unhide_post">
              <button type="submit">Megjelenítés</button>
            <?php endif; ?>
          </form>
        </article>
      <?php endforeach; ?>

      <nav class="pagination" aria-label="Lapozás">
        <ul class="pagination__list">
          <li>
            <?php if ($page > 1): ?>
              <a href="?<?= e(http_build_query(array_merge($_GET, ['page'=>$page-1]))) ?>">&laquo; Előző</a>
            <?php else: ?><span>&laquo; Előző</span><?php endif; ?>
          </li>
          <li>
            <?php if ($page < $pages): ?>
              <a href="?<?= e(http_build_query(array_merge($_GET, ['page'=>$page+1]))) ?>">Következő &raquo;</a>
            <?php else: ?><span>Következő &raquo;</span><?php endif; ?>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
  </section>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
