<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

function token_check()
{
    if (
        !isset($_POST['token']) ||
        !hash_equals($_SESSION['token'], $_POST['token'])
    ) {
        die('Invalid security token');
    }
}