<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/admin_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/settings.php";
require_once "../config/token.php";

$id = $_GET['id'] ?? null;
$id = is_numeric($id) ? (int)$id : 0;

if ($id <= 0) {
    header("Location: kelola_petugas.php");
    exit;
}

$query = mysqli_query($conn, "
    SELECT * FROM users 
    WHERE id_user='$id' AND role='petugas'
");

$petugas = mysqli_fetch_assoc($query);
if (!$petugas) {
    header("Location: kelola_petugas.php");
    exit;
}

$active    = 'admin-panel';
$subactive = 'petugas';

if (isset($_POST['simpan'])) {

    token_check();

    $nama       = trim($_POST['nama']);
    $username   = trim($_POST['username']);
    $no_petugas = trim($_POST['no_petugas']);
    $shift      = $_POST['shift'];
    $status     = $_POST['status'];
    $password   = $_POST['password_baru'] ?? '';
    $force      = isset($_POST['force_logout']) ? 1 : 0;

    // pastikan username selalu pakai @
    if ($username[0] !== '@') {
        $username = '@' . $username;
    }

    if ($nama === '' || $username === '' || $no_petugas === '' || $shift === '') {

        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Semua field wajib diisi.'
        ];
        header("Location: edit_petugas.php?id=$id");
        exit;
    }

    // username unik (kecuali dirinya)
    $cekUsername = mysqli_query($conn, "
        SELECT id_user FROM users 
        WHERE username='$username' 
        AND id_user != '$id'
    ");

    if (mysqli_num_rows($cekUsername) > 0) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Username sudah digunakan.'
        ];
        header("Location: edit_petugas.php?id=$id");
        exit;
    }

    // no petugas unik
    $cekNo = mysqli_query($conn, "
        SELECT id_user FROM users 
        WHERE no_petugas='$no_petugas'
        AND id_user != '$id'
    ");

    if (mysqli_num_rows($cekNo) > 0) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'No petugas sudah digunakan.'
        ];
        header("Location: edit_petugas.php?id=$id");
        exit;
    }

    mysqli_query($conn, "
        UPDATE users SET
            nama='$nama',
            username='$username',
            no_petugas='$no_petugas',
            shift='$shift',
            status='$status',
            force_logout='$force'
        WHERE id_user='$id'
    ");

    // reset password (opsional)
    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        mysqli_query($conn, "
            UPDATE users SET password='$hash' 
            WHERE id_user='$id'
        ");
    }

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'Data petugas berhasil diperbarui.'
    ];

    header("Location: kelola_petugas.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Petugas | e-DATA Pegawai</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body class="d-flex flex-column min-vh-100">

<?php include "../templates/topbar.php"; ?>

<main class="main-content flex-fill">
<div class="container mt-4">

    <h4 class="mb-3">
        <i class="fa fa-user-secret"></i> Edit Petugas
    </h4>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type']; ?> alert-dismissible fade show">
            <?= $_SESSION['flash']['message']; ?>
            <button class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">
        <div class="row">
            
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header font-weight-bold">Data Petugas</div>
                    <div class="card-body">

                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" name="nama" class="form-control"
                                   value="<?= htmlspecialchars($petugas['nama']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Username</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">@</span>
                                </div>
                                <input type="text" name="username" class="form-control"
                                       value="<?= ltrim($petugas['username'], '@'); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>No Petugas</label>
                            <input type="text" name="no_petugas" class="form-control"
                                   value="<?= htmlspecialchars($petugas['no_petugas']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Shift</label>
                            <select name="shift" class="form-control" required>
                                <option value="pagi" <?= $petugas['shift']=='pagi'?'selected':''; ?>>Pagi</option>
                                <option value="siang" <?= $petugas['shift']=='siang'?'selected':''; ?>>Siang</option>
                                <option value="malam" <?= $petugas['shift']=='malam'?'selected':''; ?>>Malam</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Status Akun</label>
                            <select name="status" class="form-control">
                                <option value="aktif" <?= $petugas['status']=='aktif'?'selected':''; ?>>Aktif</option>
                                <option value="nonaktif" <?= $petugas['status']=='nonaktif'?'selected':''; ?>>Nonaktif</option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header font-weight-bold">Keamanan</div>
                    <div class="card-body">

                        <div class="form-group">
                            <label>Reset Password (Opsional)</label>
                            <input type="password" name="password_baru"
                                   class="form-control"
                                   placeholder="Isi jika ingin reset">
                        </div>

                        <div class="form-check">
                            <input type="checkbox" class="form-check-input"
                                   name="force_logout" id="force"
                                   <?= $petugas['force_logout'] ? 'checked' : ''; ?>>
                            <label for="force" class="form-check-label">
                                Paksa logout petugas
                            </label>
                        </div>

                    </div>
                </div>

                <div class="card">
                    <div class="card-header font-weight-bold">Informasi Sistem</div>
                    <div class="card-body">
                        <p><b>Role:</b> Petugas</p>
                        <p><b>Last Login:</b>
                            <?= $petugas['last_login']
                                ? date('d-m-Y H:i', strtotime($petugas['last_login']))
                                : '<span class="text-muted">Belum pernah login</span>'; ?>
                        </p>
                        <p><b>Dibuat:</b>
                            <?= date('d-m-Y H:i', strtotime($petugas['created_at'])); ?>
                        </p>
                    </div>
                </div>

            </div>

        </div>

        <div class="text-right mt-3">
            <a href="kelola_petugas.php" class="btn btn-secondary">Kembali</a>
            <button type="submit" name="simpan" class="btn btn-primary">
                <i class="fa fa-save"></i> Simpan Perubahan
            </button>
        </div>

    </form>

</div>
</main>

<?php include "../templates/footer.php"; ?>

<script src="../assets/bootstrap/js/jquery.min.js"></script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
