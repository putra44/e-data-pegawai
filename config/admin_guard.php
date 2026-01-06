<?php
// config/admin_guard.php

// pastikan session sudah ada
if (!isset($_SESSION['login'])) {
    header("Location: ../auth/login.php");
    exit;
}

// pastikan role adalah admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../halaman/dashboard.php");
    exit;
}
