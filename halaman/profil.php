<?php 
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/settings.php";
require_once "../config/token.php";

$active = 'profil';

// Ambil data user
$id = $_SESSION['id_user'];
$query = mysqli_query($conn, "SELECT * FROM users WHERE id_user='$id'");
$user = mysqli_fetch_assoc($query);

$dataPetugas = null;

if ($user['role'] === 'petugas') {
    $dataPetugas = [
        'no_petugas' => $user['no_petugas'],
        'shift'      => $user['shift']
    ];
}

if (isset($_POST['update_profil'])) {

    token_check();

    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $username = trim($_POST['username']);

    // pastikan username selalu diawali @
    if ($username[0] !== '@') {
        $username = '@' . $username;
    }
    // batas maks username user
    $username = substr($username, 0, 20);

    // cek username dipakai user lain
    $cekUsername = mysqli_query($conn, "
        SELECT id_user FROM users 
        WHERE username='$username' 
        AND id_user != '$id'
    ");

    if (mysqli_num_rows($cekUsername) > 0) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Username sudah digunakan pengguna lain.'
        ];
        header("Location: profil.php");
        exit;
    }

    // upload foto jika ada
    if (!empty($_FILES['foto']['name'])) {

        $allowed = ['jpg','jpeg','png'];
        $ext  = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $size = $_FILES['foto']['size'];

        if (!in_array($ext, $allowed)) {
            $_SESSION['flash'] = ['type'=>'danger','message'=>'Format foto harus JPG, JPEG, atau PNG.'];
            header("Location: profil.php"); exit;
        }

        if ($size > 5242880) {
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => 'Ukuran foto maksimal 5MB.'
            ];
            header("Location: profil.php");
            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['foto']['tmp_name']);
        finfo_close($finfo);

        $allowedMime = ['image/jpeg', 'image/png'];

        if (!in_array($mime, $allowedMime)) {
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => 'File tidak valid sebagai gambar.'
            ];
            header("Location: profil.php");
            exit;
        }

        // hapus foto lama
        if (!empty($user['foto']) && file_exists("../assets/uploads/profil/".$user['foto'])) {
            unlink("../assets/uploads/profil/".$user['foto']);
        }

        $namaFile = 'user_'.$id.'_'.time().'.'.$ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], "../assets/uploads/profil/".$namaFile);

        mysqli_query($conn, "
            UPDATE users 
            SET nama='$nama', username='$username', foto='$namaFile' 
            WHERE id_user='$id'
        ");

        $_SESSION['foto'] = $namaFile;

    } else {
        mysqli_query($conn, "
            UPDATE users 
            SET nama='$nama', username='$username' 
            WHERE id_user='$id'
        ");
    }

    $_SESSION['nama']     = $nama;
    $_SESSION['username'] = $username;

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'Profil berhasil diperbarui.'
    ];

    header("Location: profil.php");
    exit;
}

if (isset($_POST['hapus_foto'])) {

    token_check();

    if (!empty($user['foto']) && $user['foto'] !== 'default.png') {

        $path = "../assets/uploads/profil/" . $user['foto'];
        if (file_exists($path)) {
            unlink($path);
        }

        mysqli_query($conn, "
            UPDATE users 
            SET foto=NULL 
            WHERE id_user='$id'
        ");

        $_SESSION['foto'] = null;
    }

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'Foto profil berhasil dihapus.'
    ];

    header("Location: profil.php");
    exit;
}

if (isset($_POST['ganti_password'])) {

    token_check();

    if (password_verify($_POST['password_lama'], $user['password'])) {

        $hash = password_hash($_POST['password_baru'], PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password='$hash' WHERE id_user='$id'");

        $_SESSION['flash'] = ['type'=>'success','message'=>'Password berhasil diganti.'];
    } else {
        $_SESSION['flash'] = ['type'=>'danger','message'=>'Password lama salah!'];
    }

    header("Location: profil.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil | e-DATA Pegawai</title>
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body class="d-flex flex-column min-vh-100">

<?php include "../templates/topbar.php"; ?>

<main class="main-content flex-fill">
<div class="container mt-4">

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type']; ?> alert-dismissible fade show">
            <?= $_SESSION['flash']['message']; ?>
            <button class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4 text-center">
            <div class="card">
                <div class="card-body">
                    <img src="../assets/uploads/profil/<?= $user['foto'] ?: 'default.png'; ?>"
                        class="rounded-circle mb-3"
                        width="150" height="150"
                        style="object-fit:cover;">
                    <h5><?= htmlspecialchars($user['nama']); ?></h5>
                    <p class="text-muted mb-1"><?= htmlspecialchars($user['username']); ?></p>
                    <span class="badge badge-primary mt-2 d-inline-block">
                        <?= ucfirst($user['role']); ?>
                    </span>
                    <?php if (!empty($dataPetugas)): ?>
                        <div class="text-muted small">
                            <div>
                                <i class="fa fa-id-badge"></i>
                                No Petugas: <strong><?= htmlspecialchars($dataPetugas['no_petugas']); ?></strong>
                            </div>
                            <div>
                                <i class="fa fa-clock-o"></i>
                                Shift: <strong><?= htmlspecialchars($dataPetugas['shift']); ?></strong>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header font-weight-bold">Edit Profil</div>
                <div class="card-body">

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">

                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" name="nama" class="form-control"
                                   value="<?= htmlspecialchars($user['nama']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Username</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">@</span>
                                </div>
                                <input type="text" name="username" class="form-control"
                                       value="<?= ltrim($user['username'], '@'); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Foto Profil</label>
                            <input type="file" name="foto" class="form-control-file" accept=".jpg,.jpeg,.png">
                        </div>
                        <div class="d-flex align-items-center mt-3" style="gap:10px;">
                            <?php if (!empty($user['foto'])): ?>
                            <form method="POST" class="m-0">
                                <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">
                                <button name="hapus_foto"
                                        class="btn btn-primary btn-danger btn-sm"
                                        onclick="return confirm('Hapus foto profil?')">
                                    <i class="fa fa-trash"></i> Hapus Foto
                                </button>
                            </form>
                            <?php endif; ?>
                            <button name="update_profil" class="btn btn-primary btn-sm">
                                <i class="fa fa-save"></i> Simpan Profil
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header font-weight-bold">Ganti Password</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">
                        <input type="password" name="password_lama" class="form-control mb-2" placeholder="Password Lama" required>
                        <input type="password" name="password_baru" class="form-control mb-2" placeholder="Password Baru" required>
                        <button name="ganti_password" class="btn btn-warning btn-sm">
                            <i class="fa fa-lock"></i> Ganti Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</main>

<?php include "../templates/footer.php"; ?>

<script src="../assets/bootstrap/js/jquery.min.js"></script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
