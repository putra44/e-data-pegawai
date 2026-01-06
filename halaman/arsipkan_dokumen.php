<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/token.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dokumen.php");
    exit;
}

token_check();

$id = $_POST['id'] ?? null;
$id = is_numeric($id) ? (int)$id : 0;

if ($id <= 0) {
    header("Location: dokumen.php");
    exit;
}

/* CEK DOKUMEN */
$q = mysqli_query($conn, "
    SELECT id_dokumen
    FROM dokumen
    WHERE id_dokumen = $id
      AND deleted_at IS NULL
");

if (!mysqli_fetch_assoc($q)) {
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Dokumen tidak ditemukan atau sudah diarsip'
    ];
    header("Location: dokumen.php");
    exit;
}

/* ARSIPKAN */
mysqli_query($conn, "
    UPDATE dokumen
    SET deleted_at = NOW()
    WHERE id_dokumen = $id
");

$_SESSION['flash'] = [
    'type' => 'success',
    'message' => 'Dokumen berhasil diarsipkan'
];

/* 🔥 REDIRECT KE ARSIP DOKUMEN */
header("Location: arsip_dokumen.php");
exit;