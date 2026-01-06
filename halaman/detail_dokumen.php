<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/settings.php";
require_once "../config/token.php";

$active = 'dokumen';

$id = $_GET['id'] ?? null;
$id = is_numeric($id) ? (int)$id : 0;

if ($id <= 0) {
    header("Location: dokumen.php");
    exit;
}

$query = mysqli_query($conn, "
    SELECT d.*, k.nama_kategori
    FROM dokumen d
    JOIN kategori_dokumen k ON d.id_kategori = k.id_kategori
    WHERE d.id_dokumen = $id
      AND d.deleted_at IS NULL
");

$data = mysqli_fetch_assoc($query);

if (!$data) {
    header("Location: dokumen.php");
    exit;
}

$folder = "../assets/uploads/dokumen/$id/";
$files = [];

if (is_dir($folder)) {
    $scan = scandir($folder);
    foreach ($scan as $f) {
        if ($f !== '.' && $f !== '..') {
            $files[] = $f;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Dokumen | e-DATA Pegawai</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php include "../templates/topbar.php"; ?>

<div class="main-content">
<div class="container mt-4">

<h4 class="text-center mb-4">Detail Dokumen</h4>

<?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-<?= $_SESSION['flash']['type']; ?> alert-dismissible fade show">
        <i class="fa fa-check-circle mr-1"></i>
        <?= $_SESSION['flash']['message']; ?>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
<?php unset($_SESSION['flash']); endif; ?>

<div class="card mb-3">
<div class="card-body">

<table class="table table-borderless table-sm mb-0">
    <tr>
        <th width="22%" class="text-left">No Dokumen</th>
        <td width="3%">:</td>
        <td><?= htmlspecialchars($data['no_dokumen']); ?></td>
    </tr>
    <tr>
        <th class="text-left">Nama Dokumen</th>
        <td>:</td>
        <td><?= htmlspecialchars($data['nama_dokumen']); ?></td>
    </tr>
    <tr>
        <th class="text-left">Pemilik</th>
        <td>:</td>
        <td><?= htmlspecialchars($data['nama_pemilik']); ?></td>
    </tr>
    <tr>
        <th class="text-left">Kategori</th>
        <td>:</td>
        <td>
            <span class="badge badge-info">
                <?= htmlspecialchars($data['nama_kategori']); ?>
            </span>
        </td>
    </tr>
    <tr>
        <th class="text-left">Status</th>
        <td>:</td>
        <td>
            <?php if ($data['status_dok'] === 'berlaku'): ?>
                <span class="badge badge-success">Berlaku</span>
            <?php else: ?>
                <span class="badge badge-danger">Kadaluarsa</span>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <th class="text-left">Tanggal Upload</th>
        <td>:</td>
        <td><?= date('d-m-Y H:i', strtotime($data['tanggal_upload'])); ?></td>
    </tr>
    <tr>
        <th class="text-left">Diunggah Oleh</th>
        <td>:</td>
        <td><?= htmlspecialchars($data['diunggah_oleh']); ?></td>
    </tr>
    <tr>
        <th class="align-top text-left">Deskripsi Dokumen</th>
        <td class="align-top">:</td>
        <td>
            <?= nl2br(htmlspecialchars($data['deskripsi'] ?: 'Tidak ada deskripsi')); ?>
        </td>
    </tr>
</table>

</div>
</div>

<div class="card">
<div class="card-body">

<h6 class="mb-3">File Dokumen</h6>

<?php if (count($files) > 0): ?>
    <ul class="list-group mb-3">
        <?php foreach ($files as $file): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span class="text-truncate">
                    <i class="fa fa-file-o text-dark mr-2"></i>
                    <?= htmlspecialchars($file); ?>
                </span>

                <div>
                    <a href="<?= $folder . urlencode($file); ?>"
                       class="btn btn-sm btn-primary"
                       target="_blank">
                        <i class="fa fa-download"></i>
                    </a>

                    <form method="POST" action="hapus_file_dokumen.php" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $id; ?>">
                        <input type="hidden" name="file" value="<?= htmlspecialchars($file); ?>">
                        <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">
                        <button type="submit"
                                class="btn btn-sm btn-danger"
                                onclick="return confirm('Hapus file ini dari dokumen?')">
                            <i class="fa fa-trash"></i>
                        </button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>

    <p class="text-muted text-center">Belum ada file yang diupload</p>
<?php endif; ?>

<div class="d-flex justify-content-end">
    <a href="dokumen.php" class="btn btn-secondary btn-sm mr-2">
        <i class="fa fa-arrow-left"></i> Kembali
    </a>

    <a href="edit_dokumen.php?id=<?= $data['id_dokumen']; ?>"
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
