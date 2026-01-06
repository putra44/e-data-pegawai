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

$q = mysqli_query($conn, "
    SELECT id_pegawai, status, status_data
    FROM pegawai
    WHERE id_pegawai = $id
");

if (mysqli_num_rows($q) !== 1) {
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Data pegawai tidak ditemukan.'
    ];
    header("Location: arsip_data_pegawai.php");
    exit;
}

$data = mysqli_fetch_assoc($q);

if ($data['status_data'] !== 'arsip') {
    $_SESSION['flash'] = [
        'type' => 'warning',
        'message' => 'Pegawai masih aktif di data utama. Arsipkan terlebih dahulu.'
    ];
    header("Location: pegawai.php");
    exit;
}

if ($data['status'] !== 'nonaktif') {
    $_SESSION['flash'] = [
        'type' => 'warning',
        'message' => '<i class="fa fa-info"></i> Pegawai masih berstatus AKTIF. Nonaktifkan terlebih dahulu sebelum menghapus.'
    ];
    header("Location: arsip_data_pegawai.php");
    exit;
}

$hapus = mysqli_query($conn, "
    DELETE FROM pegawai
    WHERE id_pegawai = $id
");

if ($hapus) {

    unset($_SESSION['token']);

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => '<i class="fa fa-trash"></i> Data pegawai berhasil dihapus permanen.'
    ];
} else {
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Gagal menghapus data pegawai.'
    ];
}

header("Location: arsip_data_pegawai.php");
exit;
