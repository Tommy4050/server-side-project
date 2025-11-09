<?php
require __DIR__ . '/../src/bootstrap.php';

$me = Auth::user();
if (!$me) redirect(base_url('login.php'));
$uid = (int)$me['user_id'];

$friends  = Friend::friendsOf($uid);
$recv     = Friend::pendingReceived($uid);
$sent     = Friend::pendingSent($uid);

$title = 'Barátaim';
$active = 'community';

include __DIR__ . '/partials/header.php';

$sidebarTitle = 'Barátkezelés';
ob_start(); ?>
<p><a href="<?= e(base_url('feed.php')) ?>">← Vissza a feedhez</a></p>
<?php
$sidebarContent = ob_get_clean();
include __DIR__ . '/partials/sidebar-filters.php';
?>

<header class="content__header">
  <h1 class="content__title">Barátok</h1>
</header>

<h2>Barátaim (<?= count($friends) ?>)</h2>
<?php if (!$friends): ?>
  <p>Még nincsenek barátaid.</p>
<?php else: ?>
  <ul>
    <?php foreach ($friends as $f): ?>
      <li>
        <a href="<?= e(base_url('profile_public.php')) . '?user_id=' . (int)$f['user_id'] ?>"><?= e($f['username']) ?></a>
        <small> · barátok ekkortól: <?= e($f['since']) ?></small>
        <form method="post" action="<?= e(base_url('friends_actions.php')) ?>" style="display:inline">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="unfriend">
          <input type="hidden" name="other_user_id" value="<?= (int)$f['user_id'] ?>">
          <input type="hidden" name="redirect" value="<?= e(base_url('friends.php')) ?>">
          <button type="submit">Barátság megszüntetése</button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<h2>Beérkező felkérések (<?= count($recv) ?>)</h2>
<?php if (!$recv): ?>
  <p>Nincsenek beérkező felkérések.</p>
<?php else: ?>
  <ul>
    <?php foreach ($recv as $r): ?>
      <li>
        <?= e($r['from_username']) ?> küldte · <?= e($r['created_at']) ?>
        <form method="post" action="<?= e(base_url('friends_actions.php')) ?>" style="display:inline">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="other_user_id" value="<?= (int)$r['requester_id'] ?>">
          <input type="hidden" name="redirect" value="<?= e(base_url('friends.php')) ?>">
          <input type="hidden" name="action" value="accept">
          <button type="submit">Elfogadás</button>
        </form>
        <form method="post" action="<?= e(base_url('friends_actions.php')) ?>" style="display:inline">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="other_user_id" value="<?= (int)$r['requester_id'] ?>">
          <input type="hidden" name="redirect" value="<?= e(base_url('friends.php')) ?>">
          <input type="hidden" name="action" value="decline">
          <button type="submit">Elutasítás</button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<h2>Elküldött felkérések (<?= count($sent) ?>)</h2>
<?php if (!$sent): ?>
  <p>Nincsenek elküldött felkérések.</p>
<?php else: ?>
  <ul>
    <?php foreach ($sent as $s): ?>
      <li>
        <?= e($s['to_username']) ?> · <?= e($s['created_at']) ?>
        <form method="post" action="<?= e(base_url('friends_actions.php')) ?>" style="display:inline">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="other_user_id" value="<?= (int)$s['addressee_id'] ?>">
          <input type="hidden" name="redirect" value="<?= e(base_url('friends.php')) ?>">
          <input type="hidden" name="action" value="cancel">
          <button type="submit">Visszavonás</button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
