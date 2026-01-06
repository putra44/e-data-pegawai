<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/settings.php";
require_once "../config/token.php";

$active    = 'arsip-data';
$subactive = 'arsip-dokumen';

/* =========================
   SEARCH & FILTER
========================= */
$keyword     = $_GET['q'] ?? '';
$id_kategori = $_GET['id_kategori'] ?? '';

$safe = mysqli_real_escape_string($conn, $keyword);

$where = "WHERE d.deleted_at IS NOT NULL";

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
$totalQ = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM dokumen d
    $where
");

$totalData = mysqli_fetch_assoc($totalQ)['total'];
$totalPage = ceil($totalData / $limit);

/* =========================
   DATA ARSIP DOKUMEN
========================= */
$query = mysqli_query($conn, "
    SELECT d.*, k.nama_kategori
    FROM dokumen d
    JOIN kategori_dokumen k ON d.id_kategori = k.id_kategori
    $where
    ORDER BY d.deleted_at DESC
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
    <title>Arsip Dokumen | e-DATA Pegawai</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php include "../templates/topbar.php"; ?>

<div class="main-content">
<div class="container mt-4">

<h4 class="text-center mb-4">Arsip Dokumen</h4>

<?php if (isset($_SESSION['flash'])): ?>
<div class="alert alert-<?= $_SESSION['flash']['type']; ?> alert-dismissible fade show">
    <i class="fa fa-info-circle mr-1"></i>
    <?= $_SESSION['flash']['message']; ?>
    <button type="button" class="close" data-dismiss="alert">
        <span>&times;</span>
    </button>
</div>
<?php unset($_SESSION['flash']); endif; ?>

<form method="GET" class="mb-3">
    <div class="row no-gutters">

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

        <div class="col-md-3">
            <select name="id_kategori" class="form-control"
                    onchange="this.form.submit()">
                <option value="">Semua Kategori</option>
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

<div class="card">
<div class="card-body table-responsive">

<table class="table table-bordered table-striped table-sm">
    <thead class="thead-light">
        <tr>
            <th width="5%">No.</th>
            <th>No Dokumen</th>
            <th>Nama Dokumen</th>
            <th>Nama Pemilik</th>
            <th>Kategori</th>
            <th>File</th>
            <th>Status</th>
            <th>Diarsipkan</th>
            <th>Oleh</th>
            <th width="15%">Aksi</th>
        </tr>
    </thead>
    <tbody>

    <?php if (mysqli_num_rows($query) > 0): ?>
        <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($query)): ?>
        <tr>
            <td class="text-center"><?= $no++; ?>.</td>
            <td><?= htmlspecialchars($row['no_dokumen']); ?></td>
            <td><?= htmlspecialchars($row['nama_dokumen']); ?></td>
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
            <td class="text-center"><?= date('d-m-Y', strtotime($row['deleted_at'])); ?></td>
            <td class="text-center"><?= htmlspecialchars($row['diunggah_oleh']); ?></td>

            <td class="text-center">
               <form method="POST" action="restore_dokumen.php" class="d-inline">
                    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">
                    <input type="hidden" name="id" value="<?= $row['id_dokumen']; ?>">
                    <button type="submit"
                            class="btn btn-success btn-sm"
                            onclick="return confirm('Pulihkan dokumen ini?')">
                        <i class="fa fa-undo"></i>
                    </button>
                </form>

                <?php if ($row['status_dok'] === 'kadaluarsa'): ?>
                    <form method="POST" action="hapus_dokumen_arsip.php" class="d-inline">
                        <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">
                        <input type="hidden" name="id" value="<?= $row['id_dokumen']; ?>">
                        <button class="btn btn-danger btn-sm"
                            onclick="return confirm('HAPUS PERMANEN? File akan ikut terhapus!')">
                            <i class="fa fa-trash"></i>
                        </button>
                    </form>
                <?php else: ?>
                    <button class="btn btn-secondary btn-sm" disabled>
                        <i class="fa fa-lock"></i>
                    </button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="10" class="text-center text-muted">
                Tidak ada dokumen diarsip
            </td>
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