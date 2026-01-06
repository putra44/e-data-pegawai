<?php
/**
 * MAINTENANCE GUARD
 * - Cek status maintenance dari tabel settings
 * - Jika ON:
 *   - Admin tetap boleh akses
 *   - User lain diarahkan ke halaman maintenance
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/database.php";

/* =========================
   AMBIL STATUS MAINTENANCE
========================= */
$maintenance = '0';
$message     = 'Sistem sedang dalam pemeliharaan.';

$q = mysqli_query($conn, "
    SELECT setting_key, setting_value 
    FROM settings 
    WHERE setting_key IN ('maintenance_mode', 'maintenance_message')
");

while ($row = mysqli_fetch_assoc($q)) {
    if ($row['setting_key'] === 'maintenance_mode') {
        $maintenance = $row['setting_value'];
    }
    if ($row['setting_key'] === 'maintenance_message') {
        $message = $row['setting_value'];
    }
}

/* =========================
   CEK AKSES
========================= */
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

if ($maintenance == '1' && !$is_admin) {

    // simpan pesan agar bisa dipakai di halaman maintenance
    $_SESSION['maintenance_message'] = $message;

    header("Location: /e-data-pegawai/maintenance.php");
    exit;
}
?>