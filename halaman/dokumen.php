<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/settings.php";
require_once "../config/token.php";

$active = 'dokumen';

/* =========================
   SEARCH & FILTER
========================= */
$keyword     = $_GET['q'] ?? '';
$id_kategori = $_GET['id_kategori'] ?? '';

$safe = mysqli_real_escape_string($conn, $keyword);

$where = "WHERE d.deleted_at IS NULL";

if ($keyword != '') {
    $where .= " AND (
        d.no_dokumen LIKE '%$safe%' OR
        d.nama_dokumen LIKE '%$safe%' OR
        d.nama_pemilik LIKE '%$safe%'
    )";
}

if ($id_kategori != '') {
    $id_kategori = (int)$id_kategori;
    $where .= " AND d.id_kategori = $id_kategori";
}

/* =========================
   PAGINATION
========================= */
$limit = 10;
$page  = $_GET['page'] ?? 1;
$page  = is_numeric($page) ? (int)$page : 1;
$page  = max(1, $page);
$offset = ($page - 1) * $limit;

/* =========================
   TOTAL DATA
========================= */
$totalDataQuery = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM dokumen d 
    $where
");
$totalData = mysqli_fetch_assoc($totalDataQuery)['total'];
$totalPage = ceil($totalData / $limit);

/* =========================
   DATA DOKUMEN
========================= */
$query = mysqli_query($conn, "
    SELECT d.*, k.nama_kategori
    FROM dokumen d
    JOIN kategori_dokumen k ON d.id_kategori = k.id_kategori
    $where
    ORDER BY d.id_dokumen DESC
    LIMIT $limit OFFSET $offset
");

/* =========================
   DATA KATEGORI
========================= */
$qKategori = mysqli_query($conn, "
    SELECT id_kategori, nama_kategori
    FROM kategori_dokumen
    WHERE is_active = 1
    ORDER BY nama_kategori ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dokumen | e-DATA Pegawai</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php include "../templates/topbar.php"; ?>

<div class="main-content">
<div class="container mt-4">

    <!-- JUDUL -->
    <div class="row mb-3">
        <div class="col-12 text-center">
            <h4 class="mb-0 print-title">Dokumen</h4>
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

    <!-- TOMBOL -->
    <div class="row align-items-center mb-3">
        <div class="col-12">
            <a href="tambah_dokumen.php" class="btn btn-primary btn-sm">
                <i class="fa fa-plus"></i> Tambah Dokumen
            </a>
        </div>
    </div>

        <!-- SEARCH & FILTER -->
    <form method="GET" class="mb-3">
        <div class="row no-gutters">

            <!-- SEARCH (LEBAR) -->
            <div class="col-md-9 pr-md-2 mb-2 mb-md-0">
                <div class="input-group">
                    <input type="text" name="q" class="form-control"
                        placeholder="Cari No Dokumen, Nama, atau Pemilik..."
                        value="<?= htmlspecialchars($keyword); ?>">
                    <div class="input-group-append">
                        <button class="btn btn-secondary">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- FILTER KATEGORI (KANAN) -->
            <div class="col-md-3">
                <select name="id_kategori" class="form-control"
                        onchange="this.form.submit()">
                    <option value="">Semua Kategori</option>
                    <?php mysqli_data_seek($qKategori, 0); ?>
                    <?php while ($k = mysqli_fetch_assoc($qKategori)): ?>
                        <option value="<?= $k['id_kategori']; ?>"
                            <?= ($id_kategori == $k['id_kategori']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($k['nama_kategori']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

        </div>
    </form>

    <!-- TABLE -->
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped table-sm">

                <thead class="thead-light">
                    <tr>
                        <th>No.</th>
                        <th>No Dokumen</th>
                        <th>Nama Dokumen</th>
                        <th>Nama Pemilik</th>
                        <th>Kategori</th>
                        <th>File</th>
                        <th>Status</th>
                        <th>Tanggal Upload</th>
                        <th class="aksi">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (mysqli_num_rows($query) > 0): ?>
                    <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($query)): ?>
                    <tr>
                        <td class="text-center"><?= $no++; ?>.</td>
                        <td class="text-center"><?= htmlspecialchars($row['no_dokumen']); ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['nama_dokumen']); ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['nama_pemilik']); ?></td>
                        <td class="text-center">
                            <span class="badge badge-info">
                                <?= htmlspecialchars($row['nama_kategori']); ?>
                            </span>
                        </td>
                        <td class="text-center"><i class="fa fa-paperclip mr-1"></i><?= $row['jumlah_file']; ?></td>
                        <td class="text-center">
                            <?php if ($row['status_dok'] === 'berlaku'): ?>
                                <span class="badge badge-success">Berlaku</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Kadaluarsa</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?= date('d-m-Y', strtotime($row['tanggal_upload'])); ?>
                        </td>
                        <td class="aksi text-center">
                            <a href="detail_dokumen.php?id=<?= $row['id_dokumen']; ?>"
                               class="btn btn-info btn-sm" title="Detail">
                                <i class="fa fa-eye"></i>
                            </a>

                            <a href="edit_dokumen.php?id=<?= $row['id_dokumen']; ?>"
                               class="btn btn-warning btn-sm" title="Edit">
                                <i class="fa fa-edit"></i>
                            </a>

                            <form method="POST" action="arsipkan_dokumen.php" class="d-inline">
                                <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">
                                <input type="hidden" name="id" value="<?= $row['id_dokumen']; ?>">
                                <button type="submit"
                                        class="btn btn-secondary btn-sm"
                                        onclick="return confirm('Arsipkan dokumen ini?')">
                                    <i class="fa fa-archive"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-muted text-center">
                            Data tidak ditemukan
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPage > 1): ?>
        <nav class="mt-3">
            <ul class="pagination justify-content-center">

            <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link"
                   href="?page=<?= $page-1; ?>&q=<?= urlencode($keyword); ?>&id_kategori=<?= $id_kategori; ?>">
                    &laquo;
                </a>
            </li>

            <?php
            $start = max(1, $page - 2);
            $end   = min($totalPage, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
            <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                <a class="page-link"
                   href="?page=<?= $i; ?>&q=<?= urlencode($keyword); ?>&id_kategori=<?= $id_kategori; ?>">
                    <?= $i; ?>
                </a>
            </li>
            <?php endfor; ?>

            <li class="page-item <?= ($page >= $totalPage) ? 'disabled' : ''; ?>">
                <a class="page-link"
                   href="?page=<?= $page+1; ?>&q=<?= urlencode($keyword); ?>&id_kategori=<?= $id_kategori; ?>">
                    &raquo;
                </a>
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