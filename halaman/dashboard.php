<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/settings.php";
require_once "../helpers/date.php";


$active = 'dashboard';

/* =====================
   QUERY DASHBOARD
===================== */

// TOTAL PEGAWAI
$qTotal = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pegawai");
$totalPegawai = mysqli_fetch_assoc($qTotal)['total'] ?? 0;

// PEGAWAI AKTIF
$qAktif = mysqli_query($conn, "SELECT COUNT(*) AS aktif FROM pegawai WHERE status='aktif'");
$pegawaiAktif = mysqli_fetch_assoc($qAktif)['aktif'] ?? 0;

// PEGAWAI NONAKTIF
$qNonaktif = mysqli_query($conn, "SELECT COUNT(*) AS nonaktif FROM pegawai WHERE status='nonaktif'");
$pegawaiNonaktif = mysqli_fetch_assoc($qNonaktif)['nonaktif'] ?? 0;

// ARSIP PEGAWAI
$qArsipPegawai = mysqli_query($conn, "SELECT COUNT(*) AS arsip FROM pegawai WHERE status_data='arsip'");
$arsipPegawai = mysqli_fetch_assoc($qArsipPegawai)['arsip'] ?? 0;

// TOTAL DOKUMEN
$qDokTotal = mysqli_query($conn, "SELECT COUNT(*) AS total FROM dokumen WHERE deleted_at IS NULL");
$totalDokumen = mysqli_fetch_assoc($qDokTotal)['total'] ?? 0;

// DOKUMEN BERLAKU
$qDokBerlaku = mysqli_query($conn, "SELECT COUNT(*) AS berlaku FROM dokumen WHERE status_dok='berlaku' AND deleted_at IS NULL");
$dokBerlaku = mysqli_fetch_assoc($qDokBerlaku)['berlaku'] ?? 0;

// DOKUMEN KADALUARSA
$qDokKadaluarsa = mysqli_query($conn, "SELECT COUNT(*) AS kadaluarsa FROM dokumen WHERE status_dok='kadaluarsa' AND deleted_at IS NULL");
$dokKadaluarsa = mysqli_fetch_assoc($qDokKadaluarsa)['kadaluarsa'] ?? 0;

// ARSIP DOKUMEN
$qArsipDokumen = mysqli_query($conn, "SELECT COUNT(*) AS arsip FROM dokumen WHERE deleted_at IS NOT NULL");
$arsipDokumen = mysqli_fetch_assoc($qArsipDokumen)['arsip'] ?? 0;

/* =============
  PENGUMUMAN 
================ */
$qPengumuman = mysqli_query($conn, "
    SELECT 
        p.*,
        u.nama,
        pr.id_pengumuman AS sudah_dibaca
    FROM pengumuman p
    LEFT JOIN users u 
        ON p.dibuat_oleh = u.id_user
    LEFT JOIN pengumuman_read pr 
        ON pr.id_pengumuman = p.id_pengumuman
        AND pr.id_user = '{$_SESSION['id_user']}'
    WHERE p.status = 'aktif'
    ORDER BY p.pinned DESC, p.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | e-DATA Pegawai</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php include "../templates/topbar.php"; ?>

<div class="main-content">
<div class="container mt-4">

<h4 class="mb-4">Dashboard</h4>

<?php if (!empty($_SESSION['welcome'])): ?>
<!-- MODAL WELCOME -->
<div class="modal fade" id="modalWelcome" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-info text-white position-relative justify-content-center">
                <h4 class="modal-title w-100 text-center m-0">
                    Berhasil Login
                </h4>

                <button type="button" class="close text-white position-absolute"
                        style="right:15px;"
                        data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-2">
                    Selamat datang, <b><?= htmlspecialchars($_SESSION['nama']); ?></b> 👋
                </p>
                <p class="mb-0">
                    Anda login sebagai <b><?= htmlspecialchars($_SESSION['role']); ?></b>.
                </p>
            </div>

            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-info" data-dismiss="modal">
                    Mulai
                </button>
            </div>

        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============
PENGUMUMAN 
================= -->
<?php if (mysqli_num_rows($qPengumuman) > 0): ?>
<div class="card shadow-sm mb-4">

    <div class="card-header bg-light text-center">
        <i class="fa fa-bell text-warning"></i>
        <strong class="ml-1">Pengumuman</strong>
    </div>

    <div class="card-body p-0" style="max-height:230px; overflow-y:auto;">
        <div class="accordion" id="accordionPengumuman">

            <?php $i = 0; while ($p = mysqli_fetch_assoc($qPengumuman)): $i++; ?>
            <div class="border-bottom">

                <button class="btn btn-light btn-block text-left d-flex justify-content-between align-items-center"
                    data-toggle="collapse"
                    data-target="#peng<?= $i; ?>"
                    onclick="tandaiBaca(<?= $p['id_pengumuman']; ?>)">

                    <span>
                        <span class="badge badge-danger mr-2">
                            <i class="fa fa-bullhorn"></i>
                        </span>
                        <strong><?= htmlspecialchars($p['judul']); ?></strong>

                        <?php if ($p['sudah_dibaca'] === null): ?>
                            <span class="badge badge-danger ml-2" id="badge-baru-<?= $p['id_pengumuman']; ?>">
                                BARU!
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($p['pinned'])): ?>
                            <span class="badge badge-warning ml-1">
                                <i class="fa fa-thumb-tack"></i>
                            </span>
                        <?php endif; ?>
                    </span>

                    <small class="text-muted">
                        <i class="fa fa-clock-o"></i>
                        <?= tanggal($p['created_at']); ?>
                    </small>
                </button>

                <div id="peng<?= $i; ?>" class="collapse" data-parent="#accordionPengumuman">
                    <div class="p-3 bg-white">
                        <div class="mb-2"><?= $p['isi']; ?></div>
                        <span class="badge badge-secondary">
                            <i class="fa fa-user"></i>
                            <?= htmlspecialchars($p['nama'] ?? 'Admin'); ?>
                        </span>
                    </div>
                </div>

            </div>
            <?php endwhile; ?>

        </div>
    </div>
</div>
<?php endif; ?>

<!-- =========================
     CARD STATISTIK
========================= -->
<div class="row">

    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <i class="fa fa-users fa-2x mb-2"></i>
                <h6>Total Pegawai</h6>
                <h4><?= $totalPegawai; ?></h4>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <i class="fa fa-check-circle fa-2x mb-2"></i>
                <h6>Pegawai Aktif</h6>
                <h4><?= $pegawaiAktif; ?></h4>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <i class="fa fa-user-times fa-2x mb-2"></i>
                <h6>Pegawai Nonaktif</h6>
                <h4><?= $pegawaiNonaktif; ?></h4>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card bg-dark text-white">
            <div class="card-body">
                <i class="fa fa-archive fa-2x mb-2"></i>
                <h6>Arsip Pegawai</h6>
                <h4><?= $arsipPegawai; ?></h4>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <i class="fa fa-file-text fa-2x mb-2"></i>
                <h6>Total Dokumen</h6>
                <h4><?= $totalDokumen; ?></h4>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <i class="fa fa-check fa-2x mb-2"></i>
                <h6>Dokumen Berlaku</h6>
                <h4><?= $dokBerlaku; ?></h4>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <i class="fa fa-clock-o fa-2x mb-2"></i>
                <h6>Dokumen Kadaluarsa</h6>
                <h4><?= $dokKadaluarsa; ?></h4>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card bg-secondary text-white">
            <div class="card-body">
                <i class="fa fa-folder-open fa-2x mb-2"></i>
                <h6>Arsip Dokumen</h6>
                <h4><?= $arsipDokumen; ?></h4>
            </div>
        </div>
    </div>

</div>



</div>
</div>

<?php include "../templates/footer.php"; ?>

<script src="../assets/bootstrap/js/jquery.min.js"></script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function tandaiBaca(id){
    const badge = document.getElementById('badge-baru-' + id);
    if (badge) badge.remove();
    fetch('../ajax/baca_pengumuman.php?id=' + id);
}
</script>

<?php if (!empty($_SESSION['welcome'])): ?>
<script>
$(document).ready(function () {
    $('#modalWelcome').modal('show');
});
</script>
<?php unset($_SESSION['welcome']); endif; ?>
</body>
</html>