<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/token.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: arsip_data_pegawai.php");
    exit;
}

token_check();

$id_dokumen = $_GET['id'] ?? null;
$file = $_GET['file'] ?? null;

$id_dokumen = is_numeric($id_dokumen) ? (int)$id_dokumen : 0;
$file = $file ? basename($file) : null;

if ($id_dokumen <= 0 || !$file) {
    header("Location: dokumen.php");
    exit;
}

/* CEK DOKUMEN */
$cek = mysqli_query($conn, "
    SELECT id_dokumen FROM dokumen
    WHERE id_dokumen = $id_dokumen
      AND deleted_at IS NULL
");
if (mysqli_num_rows($cek) === 0) {
    header("Location: dokumen.php");
    exit;
}

$folder = "../assets/uploads/dokumen/$id_dokumen/";
$path   = $folder . $file;

/* HAPUS FILE */
if (is_file($path)) {
    unlink($path);
}

/* UPDATE JUMLAH FILE */
$total = is_dir($folder) ? count(scandir($folder)) - 2 : 0;
mysqli_query($conn, "
    UPDATE dokumen
    SET jumlah_file = $total
    WHERE id_dokumen = $id_dokumen
");

/* FLASH MESSAGE */
$_SESSION['flash'] = [
    'type' => 'success',
    'message' => 'File berhasil dihapus'
];

/* ⬅️ REDIRECT BALIK KE DETAIL */
header("Location: detail_dokumen.php?id=$id_dokumen");
exit;