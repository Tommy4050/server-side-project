<?php
require __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Cart.php';

$me = Auth::user();
if (!$me) redirect(base_url('login.php'));

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Érvénytelen kérés.');
    }
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        throw new RuntimeException('Érvénytelen űrlap token.');
    }

    $cartItemId = (int)($_POST['cart_item_id'] ?? 0);
    if ($cartItemId <= 0) throw new RuntimeException('Hiányzó kosár tétel.');

    Cart::removeItem((int)$me['user_id'], $cartItemId);

    // Redirect back (open mini cart)
    $backUrl = $_SERVER['HTTP_REFERER'] ?? base_url('store.php');
    $parsed  = parse_url($backUrl);
    $query   = [];
    if (!empty($parsed['query'])) parse_str($parsed['query'], $query);
    $query['cart_open'] = 1;
    $newQuery = http_build_query($query);

    $rebuilt = ($parsed['scheme'] ?? '') && ($parsed['host'] ?? '')
      ? ($parsed['scheme'].'://'.$parsed['host'].(!empty($parsed['port'])?':'.$parsed['port']:'').($parsed['path']??'/').($newQuery?('?'.$newQuery):'').(!empty($parsed['fragment'])?'#'.$parsed['fragment']:''))
      : ( ($parsed['path'] ?? base_url('store.php')) . ($newQuery?('?'.$newQuery):'') . (!empty($parsed['fragment'])?'#'.$parsed['fragment']:'') );

    header('Location: ' . $rebuilt);
    exit;

} catch (Throwable $e) {
    $msg = urlencode($e->getMessage());
    redirect(base_url('store.php') . "?error={$msg}&cart_open=1");
}
