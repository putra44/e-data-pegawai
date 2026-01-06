<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/admin_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/settings.php";
require_once "../config/token.php";

$active    = 'admin-panel';
$subactive = 'petugas';

if (isset($_POST['simpan'])) {

    token_check();

    $nama       = trim($_POST['nama']);
    $username   = trim($_POST['username']);
    $password   = $_POST['password'];
    $no_petugas = trim($_POST['no_petugas']);
    $shift      = $_POST['shift'];

    // pastikan username selalu diawali @
    if ($username[0] !== '@') {
        $username = '@' . $username;
    }

    if ($nama === '' || $username === '' || $password === '' || $no_petugas === '' || $shift === '') {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Semua field wajib diisi.'
        ];
    } else {

        $cekUser = mysqli_query($conn, "SELECT id_user FROM users WHERE username='$username'");
        $cekNo   = mysqli_query($conn, "SELECT id_user FROM users WHERE no_petugas='$no_petugas'");

        if (mysqli_num_rows($cekUser) > 0) {
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => 'Username sudah digunakan.'
            ];
        } elseif (mysqli_num_rows($cekNo) > 0) {
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => 'No petugas sudah terdaftar.'
            ];
        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            mysqli_query($conn, "
                INSERT INTO users (nama, username, password, role, no_petugas, shift, status)
                VALUES ('$nama','$username','$hash','petugas','$no_petugas','$shift','aktif')
            ");

            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'Petugas berhasil ditambahkan.'
            ];

            header("Location: kelola_petugas.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Petugas | e-DATA Pegawai</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php include "../templates/topbar.php"; ?>

<div class="main-content">
<div class="container mt-4">

    <h4 class="mb-3">
        <i class="fa fa-user-plus"></i> Tambah Petugas
    </h4>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type']; ?> alert-dismissible fade show">
            <i class="fa fa-exclamation-circle"></i>
            <?= $_SESSION['flash']['message']; ?>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">

            <form method="POST">
                <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">

                <div class="form-group">
                    <label>Nama Petugas</label>
                    <input type="text" name="nama" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">@</span>
                        </div>
                        <input type="text" name="username" class="form-control" placeholder="username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password Sementara</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>No Petugas</label>
                    <input type="text" name="no_petugas" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Shift</label>
                    <select name="shift" class="form-control" required>
                        <option value="">-- Pilih Shift --</option>
                        <option value="pagi">Pagi</option>
                        <option value="siang">Siang</option>
                        <option value="malam">Malam</option>
                    </select>
                </div>

                <div class="text-right">
                    <a href="kelola_petugas.php" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Kembali
                    </a>
                    <button name="simpan" class="btn btn-primary">
                        <i class="fa fa-save"></i> Simpan Petugas
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
</div>

<?php include "../templates/footer.php"; ?>
<script src="../assets/bootstrap/js/jquery.min.js"></script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
