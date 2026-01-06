<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/settings.php";
require_once "../config/token.php";

$active = 'pegawai';

$qJabatan = mysqli_query($conn, "
    SELECT id_jabatan, nama_jabatan
    FROM kategori_jabatan
    WHERE is_active = 1
");

$qDepartemen = mysqli_query($conn, "
    SELECT id_departemen, nama_departemen
    FROM kategori_departemen
    WHERE is_active = 1
");

if (isset($_POST['simpan'])) {

    token_check();

    $nip        = mysqli_real_escape_string($conn, $_POST['nip']);
    $nama       = mysqli_real_escape_string($conn, $_POST['nama']);
    $jk         = $_POST['jenis_kelamin'];
    $id_jabatan    = (int) $_POST['id_jabatan'];
    $id_departemen = (int) $_POST['id_departemen'];

    $alamat     = mysqli_real_escape_string($conn, $_POST['alamat']);
    $status     = $_POST['status'];
    $insert = mysqli_query($conn, "
        INSERT INTO pegawai 
        (nip, nama, jenis_kelamin, id_jabatan, id_departemen, alamat, status)
        VALUES
        ('$nip', '$nama', '$jk', '$id_jabatan', '$id_departemen', '$alamat', '$status')
    ");

    if ($insert) {
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => '<i class="fa fa-check-circle"></i> Pegawai berhasil ditambahkan.'
        ];
        header("Location: pegawai.php");
        exit;
    } else {
        $error = "Gagal menyimpan data pegawai!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Pegawai | e-DATA Pegawai</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font-awesome-4.7.0/css/font-awesome.min.css">
</head>
<body>

<?php include "../templates/topbar.php"; ?>

<div class="container mt-4">
    <h4 class="mb-3">Tambah Pegawai</h4>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-warning"></i> <?= $error; ?>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">

                <div class="form-group">
                    <label>NIP</label>
                    <input type="text"
                           name="nip"
                           class="form-control"
                           maxlength="10"
                           pattern="[0-9]{1,10}"
                           inputmode="numeric"
                           oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                           placeholder="Maksimal 10 digit angka"
                           required>
                </div>

                <div class="form-group">
                    <label>Nama Pegawai</label>
                    <input type="text" name="nama" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="form-control" required>
                        <option value="">-- Pilih jenis kelamin--</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Jabatan</label>
                    <select name="id_jabatan" class="form-control" required>
                        <option value="">-- Pilih Jabatan --</option>
                        <?php while ($j = mysqli_fetch_assoc($qJabatan)): ?>
                            <option value="<?= $j['id_jabatan']; ?>">
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
                            <option value="<?= $d['id_departemen']; ?>">
                                <?= htmlspecialchars($d['nama_departemen']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat" class="form-control" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control" required>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>

                <div class="text-right">
                    <a href="pegawai.php" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left"></i> Kembali
                    </a>
                    <button type="submit" name="simpan" class="btn btn-primary btn-sm">
                        <i class="fa fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include "../templates/footer.php"; ?>
<script src="../assets/bootstrap/js/jquery.min.js"></script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
