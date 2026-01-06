<?php
session_start();

/*
 | Logout
 | - Set notifikasi logout sukses
 | - Hapus session
 | - Redirect ke login
*/

// flash message
$_SESSION['logout_success'] = true;

// hapus session login
session_unset();
session_destroy();

// start ulang session khusus flash
session_start();
$_SESSION['logout_success'] = true;

header("Location: login.php");
exit;