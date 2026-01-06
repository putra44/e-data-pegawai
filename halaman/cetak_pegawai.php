<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/maintenance_guard.php";

// SEARCH
$keyword = $_GET['q'] ?? '';
$where = "";
if ($keyword != '') {
    $safe = mysqli_real_escape_string($conn, $keyword);
    $where = "WHERE nip LIKE '%$safe%' OR nama LIKE '%$safe%'";
}

// PAGINATION (SAMA PERSIS)
$limit = 20;
$page = $_GET['page'] ?? 1;
$page = is_numeric($page) ? (int)$page : 1;
$offset = ($page - 1) * $limit;

// DATA
$query = mysqli_query($conn, "
    SELECT * FROM pegawai
    $where
    ORDER BY id_pegawai DESC
    LIMIT $limit OFFSET $offset
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Data Pegawai</title>

    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">

    <style>
        body {
            font-size: 12px;
        }
        table th, table td {
            padding: 6px !important;
        }
        @media print {
            @page {
                size: A4;
                margin: 15mm;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <h4 class="text-center mb-3">Data Pegawai</h4>

    <table class="table table-bordered table-sm text-center">
        <thead>
            <tr>
                <th width="5%">No.</th>
                <th>NIP</th>
                <th>Nama</th>
                <th>Jenis Kelamin</th>
                <th>Jabatan</th>
                <th>Departemen</th>
                <th>Alamat</th>
            </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($query) > 0): ?>
            <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($query)): ?>
            <tr>
                <td><?= $no++; ?>.</td>
                <td><?= $row['nip']; ?></td>
                <td><?= htmlspecialchars($row['nama']); ?></td>
                <td><?= $row['jenis_kelamin']; ?></td>
                <td><?= $row['jabatan']; ?></td>
                <td><?= $row['departemen']; ?></td>
                <td><?= $row['alamat']; ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">Data tidak ditemukan</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

</body>
</html>