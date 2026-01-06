<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/token.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pegawai.php");
    exit;
}

token_check();

$id = $_POST['id'] ?? null;
if (!$id || !is_numeric($id)) {
    header("Location: pegawai.php");
    exit;
}

$id = (int)$id;

$q = mysqli_query($conn, "
    SELECT id_pegawai
    FROM pegawai
    WHERE id_pegawai = $id
      AND status_data = 'aktif'
");

if (!mysqli_fetch_assoc($q)) {
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Data pegawai tidak ditemukan atau sudah diarsip.'
    ];
    header("Location: arsip_data_pegawai.php");
    exit;
}

$arsip = mysqli_query($conn, "
    UPDATE pegawai
    SET status_data = 'arsip'
    WHERE id_pegawai = $id
");

if ($arsip) {

    unset($_SESSION['token']);

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => '<i class="fa fa-check-circle"></i> Data pegawai berhasil diarsipkan.'
    ];
} else {
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Gagal mengarsipkan data pegawai.'
    ];
}

header("Location: arsip_data_pegawai.php");
exit;
