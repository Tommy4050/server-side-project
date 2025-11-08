<?php
    require __DIR__ . '/../src/bootstrap.php';
    Auth::logout();
    redirect(base_url('index.php'));
?>