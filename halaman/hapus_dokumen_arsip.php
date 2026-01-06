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

$id = $_GET['id'] ?? null;
$id = is_numeric($id) ? (int)$id : 0;

if ($id <= 0) {
    header("Location: arsip_dokumen.php");
    exit;
}

/* AMBIL DATA DOKUMEN */
$q = mysqli_query($conn, "
    SELECT id_dokumen, status_dok
    FROM dokumen
    WHERE id_dokumen = $id
      AND deleted_at IS NOT NULL
");

$data = mysqli_fetch_assoc($q);

/* CEK VALID */
if (!$data) {
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Dokumen tidak valid atau belum diarsip'
    ];
    header("Location: arsip_dokumen.php");
    exit;
}

/* BLOK JIKA STATUS MASIH BERLAKU */
if ($data['status_dok'] === 'berlaku') {
    $_SESSION['flash'] = [
        'type' => 'warning',
        'message' => 'Dokumen berstatus BERLAKU tidak boleh dihapus'
    ];
    header("Location: arsip_dokumen.php");
    exit;
}

/* HAPUS FOLDER FILE */
$folder = "../assets/uploads/dokumen/$id/";
if (is_dir($folder)) {
    foreach (glob($folder . "*") as $file) {
        unlink($file);
    }
    rmdir($folder);
}

/* HAPUS DATA DB */
mysqli_query($conn, "
    DELETE FROM dokumen
    WHERE id_dokumen = $id
");

/* FLASH MESSAGE */
$_SESSION['flash'] = [
    'type' => 'success',
    'message' => 'Dokumen berhasil dihapus permanen'
];

header("Location: arsip_dokumen.php");
exit;
