<?php
session_start();

require_once "config/database.php";
require_once "config/settings.php";

// Cek status maintenance
if (($settings['maintenance_mode'] ?? 0) == 0) {
    header("Location: auth/login.php");
    exit;
}

$message = $settings['maintenance_message'] ?? 'Sistem sedang dalam pemeliharaan. Silakan kembali nanti.';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Maintenance | <?= htmlspecialchars($settings['app_name'] ?? 'e-DATA Pegawai'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/font-awesome-4.7.0/css/font-awesome.min.css">

    <style>
        body {
            min-height: 100vh;
            background: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .maintenance-card {
            max-width: 520px;
            border-radius: 12px;
            border: none;
        }
        .maintenance-icon {
            font-size: 64px;
            color: #ffc107;
        }
    </style>
</head>

<body>

<div class="card maintenance-card shadow text-center p-4">
    <div class="card-body">

        <div class="maintenance-icon mb-3">
            <i class="fa fa-cogs"></i>
        </div>
        
        <h4 class="mb-3">Sistem Dalam Pemeliharaan..</h4>

        <p class="text-muted mb-4">
            <?= htmlspecialchars($message); ?>
        </p>

        <small class="text-muted">
            © <?= date('Y'); ?>
            <b><?= htmlspecialchars($settings['app_name'] ?? 'e-DATA Pegawai'); ?></b>

            <span class="d-none d-sm-inline">
                | <?= htmlspecialchars($settings['footer_text'] ?? 'Sistem Informasi Pegawai'); ?>
            </span>
            <span class="d-block text-muted mt-1">
                <?= htmlspecialchars($settings['institution_name']); ?>
            </span>
        </small>

    </div>
</div>

</body>
</html>
