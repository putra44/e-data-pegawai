<?php
session_start();

require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/admin_guard.php";
require_once "../config/token.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: kelola_petugas.php");
    exit;
}

token_check();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (!$id) {
    header("Location: kelola_petugas.php");
    exit;
}

// Cegah admin menonaktifkan admin
$cek = mysqli_query($conn, "
    SELECT role, status 
    FROM users 
    WHERE id_user='$id'
    LIMIT 1
");

$user = mysqli_fetch_assoc($cek);

if (!$user) {
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Petugas tidak ditemukan.'
    ];
    header("Location: kelola_petugas.php");
    exit;
}

if ($user['role'] !== 'petugas') {
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Aksi tidak diizinkan.'
    ];
    header("Location: kelola_petugas.php");
    exit;
}

if ($user['status'] === 'nonaktif') {
    $_SESSION['flash'] = [
        'type' => 'warning',
        'message' => 'Petugas sudah nonaktif.'
    ];
    header("Location: kelola_petugas.php");
    exit;
}

// NONAKTIFKAN PETUGAS + FORCE LOGOUT
mysqli_query($conn, "
    UPDATE users 
    SET status='nonaktif', force_logout=1 
    WHERE id_user='$id'
");

$_SESSION['flash'] = [
    'type' => 'success',
    'message' => 'Petugas berhasil dinonaktifkan.'
];

header("Location: kelola_petugas.php");
exit;