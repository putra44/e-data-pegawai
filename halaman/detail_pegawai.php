<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/settings.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: pegawai.php");
    exit;
}

$query = mysqli_query($conn, "
    SELECT 
        p.*,
        kj.nama_jabatan,
        kd.nama_departemen
    FROM pegawai p
    LEFT JOIN kategori_jabatan kj 
        ON p.id_jabatan = kj.id_jabatan
    LEFT JOIN kategori_departemen kd 
        ON p.id_departemen = kd.id_departemen
    WHERE p.id_pegawai = '$id'
");

$pegawai = mysqli_fetch_assoc($query);

if (!$pegawai) {
    header("Location: pegawai.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Pegawai | e-DATA Pegawai</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php include "../templates/topbar.php"; ?>

<div class="main-content">
<div class="container mt-4">

    <h4 class="mb-3">Detail Pegawai</h4>

    <div class="card">
        <div class="card-body">

            <table class="table table-bordered">
                <tr>
                    <th width="30%" class="text-left">NIP</th>
                    <td><?= htmlspecialchars($pegawai['nip']); ?></td>
                </tr>
                <tr>
                    <th class="text-left">Nama</th>
                    <td><?= htmlspecialchars($pegawai['nama']); ?></td>
                </tr>
                <tr>
                    <th class="text-left">Jenis Kelamin</th>
                    <td><?= htmlspecialchars($pegawai['jenis_kelamin']); ?></td>
                </tr>
                <tr>
                    <th class="text-left">Jabatan</th>
                    <td><?= htmlspecialchars($pegawai['nama_jabatan'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th class="text-left">Departemen</th>
                    <td><?= htmlspecialchars($pegawai['nama_departemen'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th class="text-left">Alamat</th>
                    <td><?= htmlspecialchars($pegawai['alamat']); ?></td>
                </tr>
                <tr>
                    <th class="text-left">Status</th>
                    <td>
                        <span class="badge badge-<?= $pegawai['status']=='aktif'?'success':'secondary'; ?>">
                            <?= $pegawai['status']; ?>
                        </span>
                    </td>
                </tr>
            </table>

            <div class="text-right">
                <a href="pegawai.php" class="btn btn-secondary btn-sm">
                    <i class="fa fa-arrow-left"></i> Kembali
                </a>
                <a href="edit_pegawai.php?id=<?= $pegawai['id_pegawai']; ?>" 
                   class="btn btn-warning btn-sm">
                    <i class="fa fa-edit"></i> Edit
                </a>
            </div>

        </div>
    </div>

</div>
</div>

<?php include "../templates/footer.php"; ?>

<script src="../assets/bootstrap/js/jquery.min.js"></script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
