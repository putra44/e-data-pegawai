<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/settings.php";
require_once "../config/token.php";

$active = 'pegawai';

$keyword = $_GET['q'] ?? '';
$safe = mysqli_real_escape_string($conn, $keyword);

$where = "WHERE p.status_data = 'aktif'";

if ($keyword != '') {
    $where .= " AND (
        p.nip LIKE '%$safe%' 
        OR p.nama LIKE '%$safe%'
        OR kj.nama_jabatan LIKE '%$safe%'
        OR kd.nama_departemen LIKE '%$safe%'
    )";
}

$limit = 10;
$page  = $_GET['page'] ?? 1;
$page  = is_numeric($page) ? (int)$page : 1;
$page  = max(1, $page);
$offset = ($page - 1) * $limit;

$totalDataQuery = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM pegawai p
    LEFT JOIN kategori_jabatan kj ON p.id_jabatan = kj.id_jabatan
    LEFT JOIN kategori_departemen kd ON p.id_departemen = kd.id_departemen
    $where
");
$totalData = mysqli_fetch_assoc($totalDataQuery)['total'];
$totalPage = ceil($totalData / $limit);

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
    $where
    ORDER BY p.id_pegawai DESC
    LIMIT $limit OFFSET $offset
");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pegawai | e-DATA Pegawai</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php include "../templates/topbar.php"; ?>

<div class="main-content">
<div class="container mt-4">

    <div class="row mb-3">
        <div class="col-12 text-center">
            <h4 class="mb-0 print-title">Data Pegawai</h4>
        </div>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type']; ?> alert-dismissible fade show">
            <?= $_SESSION['flash']['message']; ?>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    <?php unset($_SESSION['flash']); endif; ?>

    <div class="row align-items-center mb-3">
        <div class="col-12">
            <a href="tambah_pegawai.php" class="btn btn-primary btn-sm mr-2">
                <i class="fa fa-plus"></i> Tambah Pegawai
            </a>

            <a href="cetak_pegawai.php?q=<?= urlencode($keyword); ?>&page=<?= $page; ?>"
               target="_blank"
               class="btn btn-secondary btn-sm">
                <i class="fa fa-print"></i> Cetak
            </a>
        </div>
    </div>

    <form method="GET" class="mb-3">
        <div class="input-group">
            <input type="text" name="q" class="form-control"
                   placeholder="Cari NIP atau Nama..."
                   value="<?= htmlspecialchars($keyword); ?>">
            <div class="input-group-append">
                <button class="btn btn-secondary">
                    <i class="fa fa-search"></i>
                </button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped table-sm">

                <colgroup>
                    <col style="width:5%">
                    <col style="width:12%">
                    <col style="width:15%">
                    <col style="width:12%">
                    <col style="width:12%">
                    <col style="width:12%">
                    <col style="width:20%">
                    <col style="width:5%">
                    <col style="width:7%">
                </colgroup>

                <thead class="thead-light">
                    <tr>
                        <th>No.</th>
                        <th>NIP</th>
                        <th>Nama</th>
                        <th>Jenis Kelamin</th>
                        <th>Jabatan</th>
                        <th>Departemen</th>
                        <th>Alamat</th>
                        <th>Status</th>
                        <th class="aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($query) > 0): ?>
                    <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($query)): ?>
                    <tr>
                        <td class="text-center"><?= $no++; ?>.</td>
                        <td><?= htmlspecialchars($row['nip']); ?></td>
                        <td><?= htmlspecialchars($row['nama']); ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['jenis_kelamin']); ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['nama_jabatan'] ?? '-'); ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['nama_departemen'] ?? '-'); ?>
                        <td class="text-left"><?= htmlspecialchars($row['alamat']); ?></td>
                        <td class="text-center">
                            <?php if ($row['status'] === 'aktif'): ?>
                                <span class="badge badge-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Nonaktif</span>
                            <?php endif; ?>
                        </td>

                        <td class="aksi">
                            <a href="detail_pegawai.php?id=<?= $row['id_pegawai']; ?>" 
                            class="btn btn-info btn-sm" title="Detail">
                                <i class="fa fa-eye"></i>
                            </a>

                            <a href="edit_pegawai.php?id=<?= $row['id_pegawai']; ?>" 
                            class="btn btn-warning btn-sm" title="Edit">
                                <i class="fa fa-edit"></i>
                            </a>

                            <form method="POST"
                                action="arsipkan_pegawai.php"
                                style="display:inline;">

                                <input type="hidden" name="id"
                                    value="<?= $row['id_pegawai']; ?>">

                                <input type="hidden" name="token"
                                    value="<?= $_SESSION['token']; ?>">

                                <button type="submit"
                                        class="btn btn-secondary btn-sm"
                                        onclick="return confirm('Arsipkan data pegawai ini?')"
                                        title="Arsip">
                                    <i class="fa fa-archive"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-muted text-center">Data tidak ditemukan</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPage > 1): ?>
        <nav class="mt-3">
            <ul class="pagination justify-content-center">

            <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link"
                href="?page=<?= $page-1; ?>&q=<?= urlencode($keyword); ?>">&laquo;</a>
            </li>

            <?php
            $start = max(1, $page - 2);
            $end   = min($totalPage, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
            <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                <a class="page-link"
                href="?page=<?= $i; ?>&q=<?= urlencode($keyword); ?>">
                    <?= $i; ?>
                </a>
            </li>
            <?php endfor; ?>

            <li class="page-item <?= ($page >= $totalPage) ? 'disabled' : ''; ?>">
                <a class="page-link"
                href="?page=<?= $page+1; ?>&q=<?= urlencode($keyword); ?>">&raquo;</a>
            </li>

            </ul>
        </nav>
    <?php endif; ?>
</div>
</div>

<?php include "../templates/footer.php"; ?>

<script src="../assets/bootstrap/js/jquery.min.js"></script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
