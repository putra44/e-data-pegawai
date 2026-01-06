<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/token.php";


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: arsip_data_pegawai.php");
    exit;
}

token_check();

$id = $_POST['id'] ?? null;
if (!$id || !is_numeric($id)) {
    header("Location: arsip_data_pegawai.php");
    exit;
}

$id = (int)$id;

$restore = mysqli_query($conn, "
    UPDATE pegawai 
    SET status_data = 'aktif'
    WHERE id_pegawai = $id
");

if ($restore) {

    unset($_SESSION['token']);

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => '<i class="fa fa-info-circle mr-1"></i> Data pegawai berhasil dipulihkan.'
    ];
} else {
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Gagal memulihkan data pegawai.'
    ];
}

header("Location: pegawai.php");
exit;
