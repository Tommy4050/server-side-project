<?php
require __DIR__ . '/../src/bootstrap.php';

$me = Auth::user();
$isAdmin = $me ? (bool)(db()->query("SELECT is_admin FROM users WHERE user_id=".(int)$me['user_id'])->fetchColumn()) : false;

$errors = [];
$notice = '';

$gameFilter = ($_GET['game_id'] ?? '') !== '' ? (int)$_GET['game_id'] : null;
$userFilter = trim($_GET['username'] ?? '');
$page       = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$per        = 10;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $errors[] = 'Érvénytelen űrlap token.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'like' || $action === 'unlike') {
                if (!$me) throw new RuntimeException('Jelentkezz be a lájkoláshoz.');
                $postId = (int)($_POST['post_id'] ?? 0);
                $action === 'like' ? Post::like((int)$me['user_id'], $postId) : Post::unlike((int)$me['user_id'], $postId);
            } elseif ($action === 'comment') {
                if (!$me) throw new RuntimeException('Jelentkezz be a hozzászóláshoz.');
                $postId = (int)($_POST['post_id'] ?? 0);
                $body   = (string)($_POST['body'] ?? '');
                $parent = isset($_POST['parent_comment_id']) && $_POST['parent_comment_id'] !== ''
                            ? (int)$_POST['parent_comment_id']
                            : null;
                Post::addComment((int)$me['user_id'], $postId, $body, $parent);
            } elseif ($action === 'hide_post' && $isAdmin) {
                Post::hidePost((int)$_POST['post_id'], (int)$me['user_id'], trim($_POST['reason'] ?? ''));
            } elseif ($action === 'unhide_post' && $isAdmin) {
                Post::unhidePost((int)$_POST['post_id']);
            } elseif ($action === 'hide_comment' && $isAdmin) {
                Post::hideComment((int)$_POST['comment_id']);
            } elseif ($action === 'unhide_comment' && $isAdmin) {
                Post::unhideComment((int)$_POST['comment_id']);
            }
            // Redirect to avoid resubmission
            header('Location: ' . base_url('feed.php') . ($gameFilter ? '?game_id='.$gameFilter : ''));
            exit;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$res  = Post::feedWithTotal($me['user_id'] ?? null, $gameFilter, null, $page, $per, $isAdmin, $userFilter);
$rows = $res['rows'];
$total= $res['total'];
$pages= (int)max(1, ceil($total / $per));

$games = db()->query("SELECT game_id, title FROM games WHERE is_published=1 ORDER BY title")->fetchAll();

$title = 'Közösség';
$active = 'community';

ob_start(); ?>
<form method="get" action="">
  <div>
    <label for="game_id">Szűrés játék szerint</label><br>
    <select id="game_id" name="game_id">
      <option value="">-- összes --</option>
      <?php foreach ($games as $g): ?>
        <option value="<?= (int)$g['game_id'] ?>"<?= ($gameFilter===(int)$g['game_id']?' selected':'') ?>>
          <?= e($g['title']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label for="username">Felhasználó szerint</label>
    <input id="username" name="username" type="text" value="<?= e($userFilter) ?>" placeholder="Felhasználó neve">
  </div>
  <div>
    <button type="submit">Szűrés</button>
    <a href="<?= e(base_url('feed.php')) ?>">Szűrő törlése</a>
  </div>
  <p><a href="<?= e(base_url('upload.php')) ?>">Kép feltöltése</a></p>
</form>
<?php
$sidebarContent = ob_get_clean();
$sidebarTitle   = 'Közösségi szűrők';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar-filters.php';
?>

<header class="content__header">
  <h1 class="content__title">Közösségi képek</h1>
</header>

<p class="content__meta">Találatok: <strong><?= (int)$total ?></strong> · Oldal: <strong><?= (int)$page ?>/<?= (int)$pages ?></strong></p>


<?php if ($errors): ?>
  <div>
    <h3>Hibák:</h3>
    <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<?php if (!$rows): ?>
  <p>Még nincs poszt.</p>
<?php else: ?>
  <?php foreach ($rows as $p): ?>
    <article class="post" style="border:1px solid #ddd; padding:8px; margin-bottom:12px;">
      <header>
          <strong>
              <a href="<?= e(base_url('profile_public.php')) . '?user_id=' . (int)$p['user_id'] ?>">
                  <?= e($p['username']) ?>
              </a>
          </strong>
          <?php if (!empty($p['game_title'])): ?> a(z)
              <a href="<?= e(base_url('feed.php')) . '?game_id=' . (int)$p['game_id'] ?>"><em><?= e($p['game_title']) ?></em></a> játékból
          <?php endif; ?>
      </header>

      <div class="post__media" style="margin:8px 0;">
        <img src="<?= e(asset_url($p['image_path'])) ?>" alt="" style="max-width:100%; height:auto">
      </div>

      <?php if (!empty($p['caption'])): ?>
        <p><?= nl2br(e($p['caption'])) ?></p>
      <?php endif; ?>

      <div class="post__actions">
        <form method="post" action="" style="display:inline">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="post_id" value="<?= (int)$p['post_id'] ?>">
          <?php if ($p['liked_by_me']): ?>
            <input type="hidden" name="action" value="unlike">
            <button type="submit">Tetszik visszavonása (<?= (int)$p['likes_count'] ?>)</button>
          <?php else: ?>
            <input type="hidden" name="action" value="like">
            <button type="submit">Tetszik (<?= (int)$p['likes_count'] ?>)</button>
          <?php endif; ?>
        </form>

        <?php if ($isAdmin): ?>
          <form method="post" action="" style="display:inline">
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
        <?php endif; ?>
      </div>

      <section class="post__comments" style="margin-top:8px;">
        <h4>Hozzászólások (<?= (int)$p['comments_count'] ?>)</h4>
        <?php
          // Get parents + their replies (non-admin sees only visible ones)
          $thread = Post::commentsThreaded((int)$p['post_id'], $isAdmin);
          if (!$thread): echo "<p>Nincsenek hozzászólások.</p>";
          else:
        ?>
          <ul style="list-style:none; padding-left:0; margin:0;">
            <?php foreach ($thread as $c): ?>
              <li style="margin-bottom:12px;">
                <div>
                  <strong><?= e($c['username']) ?></strong> · <small><?= e($c['created_at']) ?></small>
                  <?php if ($c['is_hidden']): ?> · <em>Rejtett</em><?php endif; ?>
                  <div><?= nl2br(e($c['body'])) ?></div>

                  <?php if ($isAdmin): ?>
                    <form method="post" action="" style="display:inline">
                      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="comment_id" value="<?= (int)$c['comment_id'] ?>">
                      <?php if (!$c['is_hidden']): ?>
                        <input type="hidden" name="action" value="hide_comment">
                        <button type="submit">Elrejt</button>
                      <?php else: ?>
                        <input type="hidden" name="action" value="unhide_comment">
                        <button type="submit">Megjelenít</button>
                      <?php endif; ?>
                    </form>
                  <?php endif; ?>

                  <?php if ($me): ?>
                    <!-- Reply to this parent comment -->
                    <form method="post" action="" style="margin-top:6px;">
                      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="comment">
                      <input type="hidden" name="post_id" value="<?= (int)$p['post_id'] ?>">
                      <input type="hidden" name="parent_comment_id" value="<?= (int)$c['comment_id'] ?>">
                      <label for="reply<?= (int)$c['comment_id'] ?>">Válasz</label><br>
                      <textarea id="reply<?= (int)$c['comment_id'] ?>" name="body" rows="2" cols="60" maxlength="1000" required></textarea><br>
                      <button type="submit">Válasz küldése</button>
                    </form>
                  <?php endif; ?>
                </div>

                <?php if (!empty($c['replies'])): ?>
                  <ul style="list-style:none; padding-left:18px; border-left:2px solid #ccc; margin:8px 0 0;">
                    <?php foreach ($c['replies'] as $r): ?>
                      <li style="margin-bottom:8px;">
                        <strong><?= e($r['username']) ?></strong> · <small><?= e($r['created_at']) ?></small>
                        <?php if ($r['is_hidden']): ?> · <em>Rejtett</em><?php endif; ?>
                        <div><?= nl2br(e($r['body'])) ?></div>

                        <?php if ($isAdmin): ?>
                          <form method="post" action="" style="display:inline">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="comment_id" value="<?= (int)$r['comment_id'] ?>">
                            <?php if (!$r['is_hidden']): ?>
                              <input type="hidden" name="action" value="hide_comment">
                              <button type="submit">Elrejt</button>
                            <?php else: ?>
                              <input type="hidden" name="action" value="unhide_comment">
                              <button type="submit">Megjelenít</button>
                            <?php endif; ?>
                          </form>
                        <?php endif; ?>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <?php if ($me): ?>
          <!-- New TOP-LEVEL comment (no parent_comment_id) -->
          <form method="post" action="" style="margin-top:10px;">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="comment">
            <input type="hidden" name="post_id" value="<?= (int)$p['post_id'] ?>">
            <label for="cmt<?= (int)$p['post_id'] ?>">Új hozzászólás</label><br>
            <textarea id="cmt<?= (int)$p['post_id'] ?>" name="body" rows="2" cols="60" maxlength="1000" required></textarea><br>
            <button type="submit">Küldés</button>
          </form>
        <?php else: ?>
          <p><a href="<?= e(base_url('login.php')) ?>">Jelentkezz be a hozzászóláshoz</a></p>
        <?php endif; ?>
      </section>

    </article>
  <?php endforeach; ?>

  <?php if ($total > 0): ?>
    <nav class="pagination" aria-label="Lapozás">
      <ul class="pagination__list">
        <li>
          <?php if ($page > 1): ?>
            <a href="?<?= e(http_build_query(array_merge($_GET, ['page' => $page - 1]))) ?>">&laquo; Előző</a>
          <?php else: ?>
            <span>&laquo; Előző</span>
          <?php endif; ?>
        </li>
        <li>
          <?php if ($page < $pages): ?>
            <a href="?<?= e(http_build_query(array_merge($_GET, ['page' => $page + 1]))) ?>">Következő &raquo;</a>
          <?php else: ?>
            <span>Következő &raquo;</span>
          <?php endif; ?>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
