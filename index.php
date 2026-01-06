<?php
session_start();

if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: halaman/dashboard.php");
} else {
    header("Location: auth/login.php");
}
exit;