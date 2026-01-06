<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/settings.php";
require_once "../config/token.php";

$active = 'pegawai';

// VALIDASI ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: pegawai.php");
    exit;
}

// AMBIL DATA
$data = mysqli_query($conn, "SELECT * FROM pegawai WHERE id_pegawai=$id");
$pegawai = mysqli_fetch_assoc($data);
if (!$pegawai) {
    header("Location: pegawai.php");
    exit;
}

/* =========================
   AMBIL KATEGORI JABATAN
========================= */
$qJabatan = mysqli_query($conn, "
    SELECT id_jabatan, nama_jabatan
    FROM kategori_jabatan
    WHERE is_active = 1
    ORDER BY nama_jabatan ASC
");

/* =========================
   AMBIL KATEGORI DEPARTEMEN
========================= */
$qDepartemen = mysqli_query($conn, "
    SELECT id_departemen, nama_departemen
    FROM kategori_departemen
    WHERE is_active = 1
    ORDER BY nama_departemen ASC
");

// PROSES UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {

    token_check();

    $nip            = mysqli_real_escape_string($conn, $_POST['nip']);
    $nama           = mysqli_real_escape_string($conn, $_POST['nama']);
    $jenis_kelamin  = $_POST['jenis_kelamin'];
    $id_jabatan    = (int) $_POST['id_jabatan'];
    $id_departemen = (int) $_POST['id_departemen'];
    $alamat         = mysqli_real_escape_string($conn, $_POST['alamat']);
    $status         = $_POST['status'];

    // CEK NIP DUPLIKAT (KECUALI DIRI SENDIRI)
    $cekNip = mysqli_query($conn, "
        SELECT id_pegawai FROM pegawai 
        WHERE nip='$nip' AND id_pegawai != '$id'
    ");

    if (mysqli_num_rows($cekNip) > 0) {
        $error = "NIP sudah digunakan oleh pegawai lain!";
    } else {

        $update = mysqli_query($conn, "
            UPDATE pegawai SET
                nip='$nip',
                nama='$nama',
                jenis_kelamin='$jenis_kelamin',
                id_jabatan=$id_jabatan,
                id_departemen=$id_departemen,
                alamat='$alamat',
                status='$status'
            WHERE id_pegawai='$id'
        ");

        if ($update) {

            unset($_SESSION['token']);

            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => '<i class="fa fa-check-circle"></i> Data pegawai berhasil diperbarui.'
            ];
            header("Location: pegawai.php");
            exit;
        } else {
            $error = "Gagal memperbarui data!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Pegawai | e-DATA Pegawai</title>
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font-awesome-4.7.0/css/font-awesome.min.css">
</head>
<body>

<?php include "../templates/topbar.php"; ?>

<div class="main-content">
<div class="container mt-4">

<h4 class="mb-3">Edit Pegawai</h4>

<?php if (isset($error)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?= $error; ?>
    <button class="close" data-dismiss="alert">&times;</button>
</div>
<?php endif; ?>

<div class="card">
<div class="card-body">

<form method="POST">
    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">

    <div class="form-group">
        <label>NIP</label>
        <input type="text" name="nip" class="form-control"
               value="<?= htmlspecialchars($pegawai['nip']); ?>"
               maxlength="10" required>
    </div>

    <div class="form-group">
        <label>Nama Pegawai</label>
        <input type="text" name="nama" class="form-control"
               value="<?= htmlspecialchars($pegawai['nama']); ?>" required>
    </div>

    <div class="form-group">
        <label>Jenis Kelamin</label>
        <select name="jenis_kelamin" class="form-control" required>
            <option value="Laki-laki" <?= $pegawai['jenis_kelamin']=='Laki-laki'?'selected':''; ?>>
                Laki-laki
            </option>
            <option value="Perempuan" <?= $pegawai['jenis_kelamin']=='Perempuan'?'selected':''; ?>>
                Perempuan
            </option>
        </select>
    </div>

    <div class="form-group">
        <label>Jabatan</label>
        <select name="id_jabatan" class="form-control" required>
            <option value="">-- Pilih Jabatan --</option>
            <?php while ($j = mysqli_fetch_assoc($qJabatan)): ?>
                <option value="<?= $j['id_jabatan']; ?>"
                    <?= ($pegawai['id_jabatan'] == $j['id_jabatan']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($j['nama_jabatan']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Departemen</label>
        <select name="id_departemen" class="form-control" required>
            <option value="">-- Pilih Departemen --</option>
            <?php while ($d = mysqli_fetch_assoc($qDepartemen)): ?>
                <option value="<?= $d['id_departemen']; ?>"
                    <?= ($pegawai['id_departemen'] == $d['id_departemen']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($d['nama_departemen']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Alamat</label>
        <textarea name="alamat" class="form-control" rows="3" required><?= 
            htmlspecialchars($pegawai['alamat']); 
        ?></textarea>
    </div>

    <div class="form-group">
        <label>Status</label>
        <select name="status" class="form-control">
            <option value="aktif" <?= $pegawai['status']=='aktif'?'selected':''; ?>>Aktif</option>
            <option value="nonaktif" <?= $pegawai['status']=='nonaktif'?'selected':''; ?>>Nonaktif</option>
        </select>
    </div>

    <div class="text-right">
        <a href="pegawai.php" class="btn btn-secondary btn-sm">
            <i class="fa fa-arrow-left"></i> Kembali
        </a>
        <button type="submit" name="update" class="btn btn-primary btn-sm">
            <i class="fa fa-save"></i> Update
        </button>
    </div>

</form>

</div>
</div>
</div>
</div>

<script src="../assets/bootstrap/js/jquery.min.js"></script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>