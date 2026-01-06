<?php

require_once "../config/settings.php";

$active    = $active ?? '';
$subactive = $subactive ?? '';

$waNumber = '';
if (!empty($settings['admin_phone'])) {
    $waNumber = preg_replace('/^0/', '+62', $settings['admin_phone']);
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <a class="navbar-brand d-flex align-items-center font-weight-bold"
        href="../halaman/dashboard.php">

        <?php if (!empty($settings['app_logo'])) : ?>
            <img src="../assets/uploads/logo/<?= htmlspecialchars($settings['app_logo']); ?>"
                alt="Logo"
                class="mr-2"
                style="height:32px; width:auto;">
        <?php else : ?>
            <i class="fa fa-id-card mr-2"></i>
        <?php endif; ?>

        <?= htmlspecialchars($settings['app_name'] ?? 'e-DATA Pegawai'); ?>
    </a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarMain">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">

        <ul class="navbar-nav mr-auto">

            <li class="nav-item <?= ($active === 'dashboard') ? 'active' : ''; ?>">
                <a class="nav-link" href="../halaman/dashboard.php">
                    <i class="fa fa-dashboard"></i> Dashboard
                </a>
            </li>

            <li class="nav-item <?= ($active === 'pegawai') ? 'active' : ''; ?>">
                <a class="nav-link" href="../halaman/pegawai.php">
                    <i class="fa fa-users"></i> Pegawai
                </a>
            </li>

            <li class="nav-item <?= ($active === 'dokumen') ? 'active' : ''; ?>">
                <a class="nav-link" href="../halaman/dokumen.php">
                    <i class="fa fa-file-text"></i> Dokumen
                </a>
            </li>

            <li class="nav-item dropdown <?= ($active === 'arsip-data') ? 'active' : ''; ?>">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-archive"></i> Arsip Data
                </a>
                <div class="dropdown-menu">
                    <a class="dropdown-item <?= ($subactive === 'arsip-data') ? 'active' : ''; ?>"
                        href="../halaman/arsip_data_pegawai.php">
                        <i class="fa fa-users mr-2"></i> Arsip Data Pegawai
                    </a>

                    <a class="dropdown-item <?= ($subactive === 'arsip-dokumen') ? 'active' : ''; ?>"
                        href="../halaman/arsip_dokumen.php">
                        <i class="fa fa-file mr-2"></i> Arsip Dokumen
                    </a>
                </div>
            </li>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') : ?>
                <li class="nav-item dropdown <?= ($active === 'admin-panel') ? 'active' : ''; ?>">
                    <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                        <i class="fa fa-database"></i> Admin Panel
                    </a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="../halaman/kelola_petugas.php">
                            <i class="fa fa-id-card-o mr-2"></i> Kelola Petugas
                        </a>

                        <a class="dropdown-item" href="../halaman/kelola_pengumuman.php">
                            <i class="fa fa-bell mr-2"></i> Kelola Pengumuman
                        </a>

                        <a class="dropdown-item" href="../halaman/sistem.php">
                            <i class="fa fa-cogs mr-2"></i> Pengaturan Sistem
                        </a>
                    </div>
                </li>
            <?php endif; ?>
        </ul>

        <ul class="navbar-nav ml-auto">

            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle"
                   href="#"
                   data-toggle="dropdown">
                    <i class="fa fa-comments-o"></i> Bantuan
                </a>

                <div class="dropdown-menu dropdown-menu-right">

                    <?php if (!empty($waNumber)) : ?>
                        <a class="dropdown-item"
                           href="https://wa.me/<?= $waNumber; ?>"
                           target="_blank">
                            <i class="fa fa-whatsapp text-success mr-2"></i>
                            WhatsApp Admin
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($settings['admin_email'])) : ?>
                        <a class="dropdown-item"
                           href="mailto:<?= htmlspecialchars($settings['admin_email']); ?>">
                            <i class="fa fa-envelope text-primary mr-2"></i>
                            Email Admin
                        </a>
                    <?php endif; ?>

                </div>
            </li>

            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center"
                   href="#"
                   data-toggle="dropdown">

                    <img src="../assets/uploads/profil/<?= $_SESSION['foto'] ?? 'default.png'; ?>"
                         class="rounded-circle mr-2"
                         width="32"
                         height="32"
                         style="object-fit:cover;">

                    <?= htmlspecialchars($_SESSION['nama']); ?>
                </a>

                <div class="dropdown-menu dropdown-menu-right">

                    <a class="dropdown-item" href="../halaman/profil.php">
                        <i class="fa fa-user mr-1"></i> Profil Saya
                    </a>

                    <div class="dropdown-divider"></div>

                    <a class="dropdown-item text-danger"
                       href="../auth/logout.php"
                       data-toggle="modal"
                       data-target="#modalLogout">
                        <i class="fa fa-sign-out"></i> Logout
                    </a>

                    <?php if (!empty($settings['app_version'])) : ?>
                        <div class="dropdown-divider"></div>
                        <span class="dropdown-item-text text-muted small text-center">
                            <i class="fa fa-code"></i>
                            Versi <?= htmlspecialchars($settings['app_version']); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </li>
        </ul>
    </div>
</nav>

<div class="modal fade" id="modalLogout" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fa fa-sign-out"></i> Konfirmasi Logout
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <p>Apakah Anda yakin ingin keluar dari sistem?</p>
            </div>

            <div class="modal-footer">
                <button type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal">
                    Batal
                </button>

                <a href="../auth/logout.php"
                   class="btn btn-danger">
                    <i class="fa fa-sign-out"></i> Logout
                </a>
            </div>

        </div>
    </div>
</div>
