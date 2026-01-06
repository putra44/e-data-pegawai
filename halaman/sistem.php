<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/admin_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/settings.php";
require_once "../config/token.php";

$active    = 'admin-panel';
$subactive = 'sistem';

/* =========================
   AMBIL SETTING
========================= */
$settings = [];
$q = mysqli_query($conn, "SELECT * FROM settings");
while ($row = mysqli_fetch_assoc($q)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

/* =========================
   SIMPAN PENGATURAN
========================= */
if (isset($_POST['simpan_umum']) || isset($_POST['simpan_maintenance'])) {

    token_check();

    if (isset($_POST['app_version']) && $_POST['app_version'] !== '') {

        // hanya angka dan titik (contoh: 1.0.0)
        if (!preg_match('/^[0-9]+(\.[0-9]+)*$/', $_POST['app_version'])) {
            $_SESSION['error'] = "Versi aplikasi hanya boleh angka dan titik (.)";
            header("Location: sistem.php");
            exit;
        }
    }

    foreach ($_POST as $key => $value) {
        if (in_array($key, ['simpan_umum', 'simpan_maintenance'])) continue;

        $safe_key   = mysqli_real_escape_string($conn, $key);
        $safe_value = mysqli_real_escape_string($conn, $value);

        mysqli_query($conn, "
            UPDATE settings 
            SET setting_value = '$safe_value'
            WHERE setting_key = '$safe_key'
        ");
    }

    $_SESSION['success'] = "Pengaturan berhasil disimpan.";
    header("Location: sistem.php");
    exit;
}

/* =========================
   FORCE LOGOUT PETUGAS
========================= */
if (isset($_POST['force_logout_petugas'])) {

    token_check();

    mysqli_query($conn, "
        UPDATE users 
        SET force_logout = 1 
        WHERE role != 'admin'
    ");

    $_SESSION['success'] = "Semua petugas berhasil dikeluarkan dari sistem.";
    header("Location: sistem.php");
    exit;
}

// SET TIMEZONE GLOBAL (jika ada)
if (!empty($settings['timezone'])) {
    date_default_timezone_set($settings['timezone']);
}

/* =========================
   UPLOAD LOGO
========================= */
if (isset($_POST['upload_logo'])) {

    token_check();

    if (!empty($_FILES['app_logo']['name'])) {

        // 🔒 VALIDASI UKURAN (MAX 2MB)
        $maxSize = 2 * 1024 * 1024; // 2MB

        if ($_FILES['app_logo']['size'] > $maxSize) {
            $_SESSION['error'] = "Ukuran logo maksimal 2MB.";
            header("Location: sistem.php");
            exit;
        }

        $allowed = ['jpg', 'jpeg', 'png'];
        $file    = $_FILES['app_logo'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $_SESSION['error'] = "Format logo harus JPG atau PNG.";
            header("Location: sistem.php");
            exit;
        }

        $newName = 'logo_app_' . time() . '.' . $ext;
        $target  = "../assets/uploads/logo/" . $newName;

        $oldLogo = $settings['app_logo'] ?? '';

        if (move_uploaded_file($file['tmp_name'], $target)) {

            if ($oldLogo && file_exists("../assets/uploads/logo/" . $oldLogo)) {
                unlink("../assets/uploads/logo/" . $oldLogo);
            }

            mysqli_query($conn, "
                INSERT INTO settings (setting_key, setting_value)
                VALUES ('app_logo', '$newName')
                ON DUPLICATE KEY UPDATE setting_value = '$newName'
            ");

            $_SESSION['success'] = "Logo berhasil diperbarui.";
        } else {
            $_SESSION['error'] = "Gagal upload logo.";
        }
    }

    header("Location: sistem.php");
    exit;
}
/* =========================
   HAPUS LOGO
========================= */
if (isset($_POST['hapus_logo'])) {

    token_check();

    $oldLogo = $settings['app_logo'] ?? '';

    if ($oldLogo && file_exists("../assets/uploads/logo/" . $oldLogo)) {
        unlink("../assets/uploads/logo/" . $oldLogo);
    }

    mysqli_query($conn, "
        UPDATE settings
        SET setting_value = ''
        WHERE setting_key = 'app_logo'
    ");

    $_SESSION['success'] = "Logo berhasil dihapus.";
    header("Location: sistem.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengaturan Sistem | e-DATA Pegawai</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php include "../templates/topbar.php"; ?>

<div class="main-content">
<div class="container mt-4">

    <h4 class="mb-4">
        <i class="fa fa-cogs"></i> Pengaturan Sistem
    </h4>

    <?php if (isset($_SESSION['success'])) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle"></i>
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">
        <div class="card mb-4">
            <div class="card-header font-weight-bold">
                <i class="fa fa-sliders"></i> Pengaturan Umum
            </div>
            <div class="card-body">

                <div class="form-group">
                    <label>Nama Aplikasi</label>
                    <input type="text" name="app_name" class="form-control"
                        value="<?= $settings['app_name'] ?? '' ?>" required>
                </div>

                <div class="form-group">
                    <label>Nama Instansi</label>
                    <input type="text" name="institution_name" class="form-control"
                        value="<?= $settings['institution_name'] ?? '' ?>">
                </div>

                <div class="form-group">
                    <label>Deskripsi Aplikasi</label>
                    <input type="text" name="app_description" class="form-control"
                        value="<?= $settings['app_description'] ?? '' ?>">
                </div>

                <div class="form-group">
                    <label>Zona Waktu</label>
                    <select name="timezone" class="form-control">
                        <?php
                        $zones = ['Asia/Jakarta','Asia/Makassar','Asia/Jayapura'];
                        foreach ($zones as $zone) {
                            $selected = ($settings['timezone'] == $zone) ? 'selected' : '';
                            echo "<option value='$zone' $selected>$zone</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Email Admin</label>
                        <input type="email" name="admin_email" class="form-control"
                            value="<?= $settings['admin_email'] ?? '' ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label>No. HP / WhatsApp</label>
                        <input type="text" name="admin_phone" class="form-control"
                            value="<?= $settings['admin_phone'] ?? '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Teks Footer</label>
                    <input type="text" name="footer_text" class="form-control"
                        value="<?= $settings['footer_text'] ?? '' ?>">
                </div>

                <div class="form-group">
                    <label>Versi Aplikasi</label>
                    <input type="text"
                        name="app_version"
                        class="form-control"
                        value="<?= $settings['app_version'] ?? '' ?>"
                        pattern="[0-9.]+" 
                        title="Hanya angka dan titik (.)"
                        oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
                    <small class="text-muted">
                        Format: angka dan titik saja (contoh: <b>1.0.0</b>)
                    </small>
                </div>

                <div class="form-group">
                    <label>Logo Aplikasi</label><br>

                    <?php if (!empty($settings['app_logo'])) : ?>
                        <img src="../assets/uploads/logo/<?= htmlspecialchars($settings['app_logo']); ?>"
                            height="80"
                            class="mb-2 d-block">

                        <button type="submit"
                                name="hapus_logo"
                                class="btn btn-sm btn-danger mb-2"
                                onclick="return confirm('Hapus logo aplikasi?')">
                            <i class="fa fa-trash"></i> Hapus Logo
                        </button>
                    <?php else : ?>
                        <i class="fa fa-id-card fa-3x text-muted mb-2"></i>
                    <?php endif; ?>

                    <input type="file" name="app_logo" class="form-control-file mt-2">

                    <button type="submit"
                            name="upload_logo"
                            class="btn btn-secondary btn-sm mt-2">
                        <i class="fa fa-upload"></i> Upload Logo
                    </button>
                </div>

                <button type="submit" name="simpan_umum" class="btn btn-primary btn-sm">
                    <i class="fa fa-save"></i> Simpan Pengaturan
                </button>

            </div>
        </div>
    </form>

    <form method="POST">
        <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">
        <div class="card mb-4 border-warning">
            <div class="card-header font-weight-bold text-warning">
                <i class="fa fa-wrench"></i> Maintenance Mode
            </div>
            <div class="card-body">

                <?php if (($settings['maintenance_mode'] ?? 0) == 1) : ?>
        <div class="alert alert-danger d-flex align-items-center">
            <i class="fa fa-warning fa-lg mr-3"></i>
            <div>
                <strong>Sedang Maintenance!</strong><br>
                <small>Saat ini petugas tidak dapat mengakses sistem.</small>
            </div>
        </div>
    <?php else : ?>
        <div class="alert alert-success d-flex align-items-center">
            <i class="fa fa-check-circle fa-lg mr-3"></i>
            <div>
                <strong>Status Sistem Normal</strong><br>
                <small>Sistem berjalan normal dan dapat diakses.</small>
            </div>
        </div>
    <?php endif; ?>

                <div class="form-group">
                    <label>Status Maintenance</label>
                    <select name="maintenance_mode" id="maintenance_mode" class="form-control">
                        <option value="0" <?= ($settings['maintenance_mode'] == 0) ? 'selected' : '' ?>>OFF</option>
                        <option value="1" <?= ($settings['maintenance_mode'] == 1) ? 'selected' : '' ?>>ON</option>
                    </select>
                </div>

                <div class="form-group" id="maintenance_message_wrapper">
                    <label>Pesan Maintenance</label>
                    <textarea name="maintenance_message" class="form-control" rows="3"><?= $settings['maintenance_message'] ?? '' ?></textarea>
                </div>

                <button type="submit" name="simpan_maintenance" class="btn btn-warning">
                    <i class="fa fa-refresh"></i> Ubah
                </button>

                <hr>
                <button type="button"
                        class="btn btn-danger"
                        data-toggle="modal"
                        data-target="#modalForceLogout">
                    <i class="fa fa-sign-out"></i> Force Logout
                </button>
            </div>
        </div>
    </form>
</div>
</div>

<?php include "../templates/footer.php"; ?>

<script src="../assets/bootstrap/js/jquery.min.js"></script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
    function toggleMaintenanceMessage() {
        const mode = document.getElementById('maintenance_mode').value;
        const wrapper = document.getElementById('maintenance_message_wrapper');
        wrapper.style.display = (mode === '1') ? 'block' : 'none';
    }
    document.getElementById('maintenance_mode').addEventListener('change', toggleMaintenanceMessage);
    toggleMaintenanceMessage();
</script>
<!-- MODAL KONFIRMASI FORCE LOGOUT -->
<div class="modal fade" id="modalForceLogout" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fa fa-warning"></i> Konfirmasi Force Logout
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <p>Apakah Anda yakin ingin <b>memaksa logout semua petugas</b>?</p>
                <small class="text-muted">
                    Tindakan ini tidak bisa dibatalkan.
                </small>
            </div>

            <div class="modal-footer">
                <button type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal">
                    Batal
                </button>

                <form method="POST">
                    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">
                    <button type="submit"
                            name="force_logout_petugas"
                            class="btn btn-danger">
                        Ya, Logout Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>